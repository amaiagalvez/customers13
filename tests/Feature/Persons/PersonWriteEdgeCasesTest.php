<?php

namespace Entities13\Tests\Feature\Persons;

use PDOException;
use Entities13\Models\Entity;
use Entities13\Models\Person;
use Entities13\Tests\TestCase;
use Entities13\Models\PersonType;
use Illuminate\Database\QueryException;
use PHPUnit\Framework\Attributes\DataProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * The branches of the person and person type writes that only a JSON caller, a concurrent write or
 * a narrower account reach.
 */
class PersonWriteEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_person_options_offer_the_matching_active_persons(): void
    {
        $this->actingAs($this->createAdminUser());
        $person = Person::factory()->create(['name' => 'Ander', 'last_name_1' => null, 'last_name_2' => null]);

        $this->getJson(route('persons.options', ['q' => 'Ander']))
            ->assertOk()
            ->assertJsonPath('results.0.id', $person->id);
    }

    public function test_the_person_type_options_offer_the_matching_active_types(): void
    {
        $this->actingAs($this->createAdminUser());
        $type = PersonType::factory()->create(['name_eu' => 'Bazkidea']);

        $this->getJson(route('person-types.options', ['q' => 'Bazki']))
            ->assertOk()
            ->assertJsonPath('results.0.id', $type->id);
    }

    public function test_the_options_reject_an_account_without_the_view_permission(): void
    {
        $this->actingAs($this->createUserWithPermissions([]));

        $this->getJson(route('persons.options'))->assertForbidden();
        $this->getJson(route('person-types.options'))->assertForbidden();
    }

    public function test_a_json_store_returns_the_created_person(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->postJson(route('persons.store'), ['name' => 'Leire', 'lang' => 'eu'])
            ->assertCreated()
            ->assertJsonPath('name', 'Leire')
            ->assertJsonStructure(['id', 'name', 'last_name_1', 'last_name_2']);
    }

    public function test_a_json_store_returns_the_created_person_type(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->postJson(route('person-types.store'), ['name_eu' => 'Bazkidea'])
            ->assertCreated()
            ->assertJsonPath('name_eu', 'Bazkidea');
    }

    public function test_a_json_store_over_a_trashed_type_name_is_a_conflict(): void
    {
        $this->actingAs($this->createAdminUser());
        PersonType::factory()->trashed()->create(['name_eu' => 'Bazkidea']);

        $this->postJson(route('person-types.store'), ['name_eu' => 'Bazkidea'])
            ->assertStatus(409)
            ->assertJsonPath('errors.name_eu.0', __('basics13::messages.deleted_name_conflict', ['name' => 'Bazkidea']));
    }

    public function test_a_type_trashed_while_the_person_is_saved_is_a_validation_error(): void
    {
        $this->actingAs($this->createAdminUser());
        $type = PersonType::factory()->create();
        // The request validated the type as live; it is trashed before the controller locks it.
        Person::creating(static function () use ($type): void {
            $type->delete();
        });

        $this->post(route('persons.store'), ['name' => 'Leire', 'lang' => 'eu', 'person_type_ids' => [$type->id]])
            ->assertSessionHasErrors(['person_type_ids' => __('validation.exists', ['attribute' => __('Person types')])]);

        $this->assertDatabaseCount('ENT_persons', 0);
    }

    public function test_an_account_without_the_entities_view_cannot_pick_an_entity(): void
    {
        $this->actingAs($this->createUserWithPermissions(['persons.view', 'persons.create']));
        $entity = Entity::factory()->create();

        $this->post(route('persons.store'), ['name' => 'Leire', 'lang' => 'eu', 'entity_id' => $entity->id])
            ->assertSessionHasErrors('entity_id');

        $this->assertDatabaseCount('ENT_persons', 0);
    }

    /**
     * @return array<string, array{0: 'store'|'update', 1: string, 2: string}>
     */
    public static function typeRaces(): array
    {
        return [
            'store on the partial index of name_fr' => ['store', 'UNIQUE constraint failed: ENT_person_types.name_fr', 'name_fr'],
            'update on an unnamed index' => ['update', 'UNIQUE constraint failed', 'name_eu'],
        ];
    }

    /**
     * @param  'store'|'update'  $operation
     */
    #[DataProvider('typeRaces')]
    public function test_a_type_unique_race_reports_the_colliding_language(string $operation, string $message, string $field): void
    {
        $this->actingAs($this->createAdminUser());
        $type = PersonType::factory()->create(['name_eu' => 'Jatorrizkoa']);
        $exception = new PDOException($message);
        $exception->errorInfo = ['23000', 19, $message];
        $failure = static function () use ($exception): never {
            throw new QueryException('sqlite', 'write', [], $exception);
        };
        $payload = ['name_eu' => 'Lehiakidea', 'name_es' => 'Competidor', 'name_fr' => 'Concurrent'];

        if ($operation === 'update') {
            PersonType::updating($failure);
            $response = $this->put(route('person-types.update', $type), $payload);
        } else {
            PersonType::creating($failure);
            $response = $this->post(route('person-types.store'), $payload);
        }

        $response->assertSessionHasErrors($field);
        $this->assertDatabaseCount('ENT_person_types', 1);
        $this->assertSame('Jatorrizkoa', $type->refresh()->name_eu);
    }
}
