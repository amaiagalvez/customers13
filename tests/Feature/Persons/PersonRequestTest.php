<?php

namespace Entities13\Tests\Feature\Persons;

use Entities13\Models\Entity;
use Entities13\Models\Person;
use Entities13\Tests\TestCase;
use Entities13\Models\PersonType;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * The rules that need the database: the entity and the types offered are the active ones, and an
 * edit keeps an archived one the person already holds.
 */
class PersonRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->createAdminUser());
    }

    public function test_name_needs_two_characters(): void
    {
        $this->post(route('persons.store'), ['name' => 'A'])->assertSessionHasErrors('name');
    }

    public function test_every_supported_language_is_accepted(): void
    {
        foreach (['eu', 'es', 'fr', 'en'] as $lang) {
            $this->post(route('persons.store'), ['name' => 'Test', 'lang' => $lang])->assertSessionHasNoErrors();
        }

        $this->assertSame(4, Person::query()->count());
    }

    public function test_blank_optional_fields_are_stored_as_null(): void
    {
        $this->post(route('persons.store'), ['name' => '  Leire  ', 'last_name_1' => '  ', 'document_number' => ''])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ENT_persons', ['name' => 'Leire', 'last_name_1' => null, 'document_number' => null]);
    }

    public function test_an_active_entity_can_be_chosen(): void
    {
        $entity = Entity::factory()->create();

        $this->post(route('persons.store'), ['name' => 'Test', 'entity_id' => $entity->id])->assertSessionHasNoErrors();
    }

    public function test_an_archived_entity_cannot_be_chosen(): void
    {
        $entity = Entity::factory()->archived()->create();

        $this->post(route('persons.store'), ['name' => 'Test', 'entity_id' => $entity->id])->assertSessionHasErrors('entity_id');
    }

    public function test_active_types_can_be_chosen(): void
    {
        $types = PersonType::factory()->count(2)->create();

        $this->post(route('persons.store'), ['name' => 'Test', 'person_type_ids' => $types->modelKeys()])->assertSessionHasNoErrors();
    }

    public function test_an_archived_type_cannot_be_chosen(): void
    {
        $type = PersonType::factory()->archived()->create();

        $this->post(route('persons.store'), ['name' => 'Test', 'person_type_ids' => [$type->id]])->assertSessionHasErrors('person_type_ids.0');
    }

    public function test_an_edit_keeps_an_archived_type_the_person_already_holds(): void
    {
        $type = PersonType::factory()->archived()->create();
        $person = Person::factory()->withTypes([$type])->create();

        $this->put(route('persons.update', $person), ['name' => $person->name, 'person_type_ids' => [$type->id]])
            ->assertSessionHasNoErrors();

        $this->assertSame([$type->id], $person->personTypes()->pluck('person_type_id')->all());
    }
}
