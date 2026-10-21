<?php

namespace Entities13\Tests\Feature\EntityTypes;

use App\Models\User;
use Entities13\Models\Entity;
use Entities13\Tests\TestCase;
use Entities13\Models\EntityType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Basics13\Support\Validation\MaxLength;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EntityTypeCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('entity-types.index'))->assertRedirect(route('login'));
        $this->post(route('entity-types.store'), ['name_eu' => 'Guest type'])
            ->assertRedirect(route('login'));
        $this->assertDatabaseCount('ENT_entity_types', 0);
    }

    public function test_unverified_users_cannot_create_entity_types(): void
    {
        $user = User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused']);

        $this->actingAs($user)
            ->post(route('entity-types.store'), ['name_eu' => 'Unverified type'])
            ->assertRedirect(route('verification.notice'));
        $this->assertDatabaseCount('ENT_entity_types', 0);
    }

    public function test_empty_entity_type_list_shows_its_empty_state(): void
    {
        $this->actingAs($this->user())
            ->get(route('entity-types.index'))
            ->assertOk()
            ->assertSee('data-test="entity-type-advanced-search"', false)
            ->assertSee('No records yet.');
    }

    public function test_authenticated_users_can_create_update_and_delete_entity_types(): void
    {
        $this->actingAs($this->user());

        $this->post(route('entity-types.store'), [
            'name_eu' => 'Bezero mota',
            'name_es' => 'Tipo de cliente',
            'name_fr' => 'Type de client',
            'name_en' => 'Entity type',
        ])
            ->assertRedirect(route('entity-types.index'))
            ->assertSessionHas('status', __('basics13::messages.created'));

        $entityType = EntityType::query()->sole();
        $this->assertSame('Bezero mota', $entityType->name_eu);
        $this->assertSame('Entity type', $entityType->name_en);

        $this->get(route('entity-types.index'))
            ->assertOk()
            ->assertSee('entity-type-create')
            ->assertSee('aria-labelledby="entity-type-form-heading"', false)
            ->assertSee('id="entity-type-form-heading"', false)
            ->assertSee('entity-type-edit-'.$entityType->id)
            ->assertSee('data-test="entity-type-name-'.$entityType->id.'"', false)
            ->assertSee('Bezero mota')
            ->assertSee('Tipo de cliente')
            ->assertSee('Type de client')
            ->assertSee('Entity type');

        $this->put(route('entity-types.update', $entityType), [
            'name_eu' => 'Bezero mota berria',
            'name_es' => 'Tipo de cliente nuevo',
        ])
            ->assertRedirect(route('entity-types.index'))
            ->assertSessionHas('status', __('basics13::messages.updated'));
        $this->assertDatabaseHas('ENT_entity_types', [
            'id' => $entityType->id,
            'name_eu' => 'Bezero mota berria',
            'name_es' => 'Tipo de cliente nuevo',
        ]);

        $this->delete(route('entity-types.destroy', $entityType))
            ->assertRedirect(route('entity-types.index'))
            ->assertSessionHas('status', __('basics13::messages.moved_to_trash'));
        $this->assertSoftDeleted($entityType);
    }

    public function test_the_list_shows_the_four_language_columns(): void
    {
        $this->actingAs($this->user());
        EntityType::factory()->create([
            'name_eu' => 'Mota euskaraz',
            'name_es' => 'Tipo en castellano',
            'name_fr' => 'Type en français',
            'name_en' => 'Type in English',
        ]);

        $this->get(route('entity-types.index'))
            ->assertOk()
            ->assertSee(__('Name (eu)'))
            ->assertSee(__('Name (es)'))
            ->assertSee(__('Name (fr)'))
            ->assertSee(__('Name (en)'))
            ->assertSee('Mota euskaraz')
            ->assertSee('Tipo en castellano')
            ->assertSee('Type en français')
            ->assertSee('Type in English');
    }

    public function test_each_language_name_is_unique_among_the_types_outside_the_trash(): void
    {
        $this->actingAs($this->user());
        EntityType::factory()->create(['name_es' => 'Duplicado']);

        $this->from(route('entity-types.index'))
            ->post(route('entity-types.store'), [
                'name_eu' => 'Mota berria',
                'name_es' => 'Duplicado',
            ])
            ->assertRedirect(route('entity-types.index'))
            ->assertSessionHasErrors('name_es');

        $this->assertSame(1, EntityType::query()->where('name_es', 'Duplicado')->count());
    }

    public function test_name_eu_is_required_and_at_least_two_characters(): void
    {
        $this->actingAs($this->user());

        $this->from(route('entity-types.index'))
            ->post(route('entity-types.store'), ['name_es' => 'Solo castellano'])
            ->assertSessionHasErrors('name_eu');

        $this->from(route('entity-types.index'))
            ->post(route('entity-types.store'), ['name_eu' => 'A'])
            ->assertSessionHasErrors('name_eu');

        $this->assertDatabaseCount('ENT_entity_types', 0);
    }

    public function test_the_other_languages_are_optional_and_blank_ones_are_stored_as_null(): void
    {
        $this->actingAs($this->user());

        $this->post(route('entity-types.store'), [
            'name_eu' => 'Mota',
            'name_es' => '',
            'name_fr' => '   ',
        ])
            ->assertRedirect(route('entity-types.index'))
            ->assertSessionHasNoErrors();

        $entityType = EntityType::query()->sole();
        $this->assertNull($entityType->name_es);
        $this->assertNull($entityType->name_fr);
        $this->assertNull($entityType->name_en);
    }

    public function test_json_creation_trims_the_names_and_returns_the_created_record(): void
    {
        $this->actingAs($this->user());

        $this->postJson(route('entity-types.store'), [
            'name_eu' => '  Bezero mota  ',
            'name_es' => '  Tipo de cliente  ',
        ])
            ->assertCreated()
            ->assertJsonStructure(['id', 'name_eu'])
            ->assertJsonPath('name_eu', 'Bezero mota');

        $this->assertDatabaseHas('ENT_entity_types', [
            'name_eu' => 'Bezero mota',
            'name_es' => 'Tipo de cliente',
        ]);
    }

    public function test_html_creation_opens_the_restore_conflict_for_a_trashed_name(): void
    {
        $this->actingAs($this->user());
        $entityType = EntityType::factory()->trashed()->create([
            'name_eu' => 'Mota zaharkitua',
            'name_es' => 'Tipo en la papelera',
        ]);

        $this->post(route('entity-types.store'), ['name_eu' => 'Mota zaharkitua'])
            ->assertRedirect(route('entity-types.index'))
            ->assertSessionHas('deleted_entity_type_conflict', [
                'id' => $entityType->id,
                'name_eu' => 'Mota zaharkitua',
                'name_es' => 'Tipo en la papelera',
                'name_fr' => $entityType->name_fr,
                'name_en' => $entityType->name_en,
            ]);

        $this->assertSame(0, EntityType::query()->where('name_eu', 'Mota zaharkitua')->count());
    }

    public function test_json_creation_requires_explicit_reuse_of_a_trashed_name(): void
    {
        $this->actingAs($this->user());
        $entityType = EntityType::factory()->trashed()->create(['name_eu' => 'Mota zaharkitua']);

        $this->postJson(route('entity-types.store'), ['name_eu' => $entityType->name_eu])
            ->assertConflict()
            ->assertJsonPath('errors.name_eu.0', __('basics13::messages.deleted_name_conflict', [
                'name' => $entityType->name_eu,
            ]));

        $this->postJson(route('entity-types.store'), [
            'name_eu' => $entityType->name_eu,
            'reuse_deleted_name' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('name_eu', $entityType->name_eu);

        $this->assertSoftDeleted($entityType);
        $this->assertSame(1, EntityType::query()->where('name_eu', $entityType->name_eu)->count());
    }

    public function test_a_type_a_entity_holds_cannot_be_deleted(): void
    {
        $this->actingAs($this->user());
        $entityType = EntityType::factory()->create();
        Entity::factory()->create()->entityTypes()->attach($entityType);

        $this->delete(route('entity-types.destroy', $entityType))
            ->assertRedirect(route('entity-types.index'))
            ->assertSessionHas('error', __('basics13::messages.cannot_delete_related'));

        $this->assertNotSoftDeleted($entityType);
        $this->assertDatabaseHas('ENT_entity_entity_type', ['entity_type_id' => $entityType->id]);
    }

    public function test_a_type_only_a_trashed_entity_holds_still_cannot_be_deleted(): void
    {
        $this->actingAs($this->user());
        $entityType = EntityType::factory()->create();
        $entity = Entity::factory()->create();
        $entity->entityTypes()->attach($entityType);
        $entity->delete();

        $this->assertSame(1, DB::table('ENT_entity_entity_type')
            ->where('entity_type_id', $entityType->id)->count());

        $this->delete(route('entity-types.destroy', $entityType))
            ->assertSessionHas('error', __('basics13::messages.cannot_delete_related'));

        $this->assertNotSoftDeleted($entityType);
    }

    public function test_a_type_with_entities_offers_archive_instead_of_delete(): void
    {
        $this->actingAs($this->user());
        $held = EntityType::factory()->create();
        Entity::factory()->create()->entityTypes()->attach($held);

        $this->get(route('entity-types.index'))
            ->assertOk()
            ->assertSee('data-test="entity-type-archive-'.$held->id.'"', false)
            ->assertDontSee('data-test="entity-type-delete-'.$held->id.'"', false)
            ->assertSee(__('basics13::messages.archive_instead_of_delete'));
    }

    public function test_store_converts_a_concurrent_duplicate_name_into_a_validation_error(): void
    {
        $this->actingAs($this->user());
        $name = 'Mota konkurrentea';
        $this->createDuplicateNameAfterValidation($name);

        $this->postJson(route('entity-types.store'), ['name_eu' => $name])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name_eu');

        $this->assertDatabaseCount('ENT_entity_types', 1);
    }

    public function test_store_uses_the_euskera_field_for_an_unrecognized_unique_constraint(): void
    {
        $this->actingAs($this->user());
        $existingType = EntityType::factory()->create([
            'name_eu' => 'Mota gordea',
            'name_es' => null,
            'name_fr' => null,
            'name_en' => null,
        ]);
        EntityType::creating(static function (EntityType $entityType) use ($existingType): void {
            $entityType->id = $existingType->id;
        });

        $this->postJson(route('entity-types.store'), ['name_eu' => 'Beste mota'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name_eu');

        $this->assertDatabaseCount('ENT_entity_types', 1);
        $this->assertDatabaseHas('ENT_entity_types', [
            'id' => $existingType->id,
            'name_eu' => 'Mota gordea',
        ]);
    }

    public function test_update_converts_a_concurrent_duplicate_name_into_a_validation_error(): void
    {
        $this->actingAs($this->user());
        $entityType = EntityType::factory()->create(['name_eu' => 'Mota originala']);
        $name = 'Mota konkurrentea';
        $this->createDuplicateNameAfterValidation($name);

        $this->putJson(route('entity-types.update', $entityType), ['name_eu' => $name])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name_eu');

        $this->assertDatabaseHas('ENT_entity_types', [
            'id' => $entityType->id,
            'name_eu' => 'Mota originala',
        ]);
        $this->assertDatabaseCount('ENT_entity_types', 2);
    }

    public function test_creation_requires_authorization(): void
    {
        $this->actingAs($this->user());
        Gate::before(static fn (): bool => false);

        $this->post(route('entity-types.store'), ['name_eu' => 'Mota'])->assertForbidden();

        $this->assertDatabaseCount('ENT_entity_types', 0);
    }

    public function test_notes_longer_than_the_configured_maximum_are_rejected(): void
    {
        $this->actingAs($this->user());

        $this->from(route('entity-types.index'))
            ->post(route('entity-types.store'), [
                'name_eu' => 'Mota',
                'notes' => str_repeat('a', MaxLength::longText() + 1),
            ])
            ->assertSessionHasErrors('notes');

        $this->assertDatabaseCount('ENT_entity_types', 0);
    }

    public function test_the_edit_form_is_bound_to_the_saved_names(): void
    {
        $this->actingAs($this->user());
        $entityType = EntityType::factory()->create([
            'name_eu' => 'Mota',
            'name_es' => 'Tipo',
            'name_fr' => 'Type',
            'name_en' => 'Kind',
            'notes' => 'Type notes',
        ]);

        $this->get(route('entity-types.index'))
            ->assertOk()
            ->assertSee('x-model="form.name_eu"', false)
            ->assertSee('data-test="entity-type-name_eu"', false)
            ->assertViewHas('list', function (array $list) use ($entityType): bool {
                $payload = $list['rows'][0]['editPayload'];

                return $list['rows'][0]['id'] === $entityType->id
                    && $payload['name_eu'] === 'Mota'
                    && $payload['name_es'] === 'Tipo'
                    && $payload['name_fr'] === 'Type'
                    && $payload['name_en'] === 'Kind'
                    && $payload['notes'] === 'Type notes';
            });
    }

    /**
     * A write that lost the race to a concurrent one is refused by the database, and the request
     * turns that refusal into the validation error a form shows on the field.
     */
    private function createDuplicateNameAfterValidation(string $name): void
    {
        $table = (new EntityType)->getTable();
        $competitorCreated = false;

        DB::listen(static function (QueryExecuted $query) use ($name, $table, &$competitorCreated): void {
            if (
                $competitorCreated
                || ! str_starts_with(strtolower(ltrim($query->sql)), 'select')
                || ! str_contains(strtolower($query->sql), strtolower($table))
                || ! in_array($name, $query->bindings, true)
            ) {
                return;
            }

            $competitorCreated = true;
            EntityType::factory()->create(['name_eu' => $name]);
        });
    }

    private function user(): User
    {
        return User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]);
    }
}
