<?php

namespace entities13\Tests\Feature\Entities;

use App\Models\User;
use entities13\Tests\TestCase;
use entities13\Models\Entity;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Basics13\Support\Validation\MaxLength;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EntityCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('entities.index'))->assertRedirect(route('login'));
        $this->post(route('entities.store'), ['name' => 'Guest entity'])
            ->assertRedirect(route('login'));
        $this->assertDatabaseCount('CUM_entities', 0);
    }

    public function test_unverified_users_cannot_create_entities(): void
    {
        $user = User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused']);

        $this->actingAs($user)
            ->post(route('entities.store'), ['name' => 'Unverified entity'])
            ->assertRedirect(route('verification.notice'));
        $this->assertDatabaseCount('CUM_entities', 0);
    }

    public function test_empty_entity_list_shows_its_empty_state(): void
    {
        $this->actingAs(User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()]))
            ->get(route('entities.index'))
            ->assertOk()
            ->assertSee('No records yet.');
    }

    public function test_authenticated_users_can_create_update_and_delete_entities(): void
    {
        $this->actingAs(User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()]));

        $response = $this->post(route('entities.store'), ['name' => 'Ane Bezeroa']);
        $response->assertRedirect(route('entities.index'))
            ->assertSessionHas('status', __('basics13::messages.created'));

        $entity = Entity::query()->firstOrFail();
        $this->assertSame('Ane Bezeroa', $entity->name);

        $this->get(route('entities.index'))
            ->assertOk()
            ->assertSee('entity-create')
            ->assertSee('aria-labelledby="entity-form-heading"', false)
            ->assertSee('id="entity-form-heading"', false)
            ->assertSee('entity-edit-' . $entity->id);

        $this->put(route('entities.update', $entity), ['name' => 'Jon Bezeroa'])
            ->assertRedirect(route('entities.index'))
            ->assertSessionHas('status', __('basics13::messages.updated'));
        $this->assertDatabaseHas('CUM_entities', ['id' => $entity->id, 'name' => 'Jon Bezeroa']);

        $this->delete(route('entities.destroy', $entity))
            ->assertRedirect(route('entities.index'))
            ->assertSessionHas('status', __('basics13::messages.moved_to_trash'));
        $this->assertSoftDeleted($entity);
    }

    public function test_entity_store_persists_only_validated_attributes(): void
    {
        $user = $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));

        $this->post(route('entities.store'), [
            'name' => 'Validated entity',
            'id' => 999999,
            'created_at' => '2000-01-01 00:00:00',
        ])->assertRedirect(route('entities.index'));

        $this->assertDatabaseHas('CUM_entities', [
            'name' => 'Validated entity',
        ]);
        $this->assertDatabaseMissing('CUM_entities', [
            'name' => 'Validated entity',
            'id' => 999999,
        ]);
        $this->assertDatabaseMissing('CUM_entities', [
            'name' => 'Validated entity',
            'created_at' => '2000-01-01 00:00:00',
        ]);
    }

    /**
     * Nothing in this package hangs from a entity, so the refusal comes from the hook an
     * integration package registers on the model. What this package owns is the outcome: the
     * entity stays put and the redirect says why.
     */
    public function test_a_refused_delete_reports_the_error_and_leaves_the_entity_out_of_the_trash(): void
    {
        $this->actingAs(User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()]));
        $entity = Entity::factory()->create();
        Entity::deleting(static fn(): bool => false);

        $this->delete(route('entities.destroy', $entity))
            ->assertRedirect(route('entities.index'))
            ->assertSessionHas('error', __('Cannot be deleted while it has related records.'));

        $this->assertNotSoftDeleted($entity);
    }

    public function test_json_entity_creation_trims_the_name_and_returns_the_created_record(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));

        $this->postJson(route('entities.store'), ['name' => '  Ane Bezeroa  '])
            ->assertCreated()
            ->assertJsonStructure(['id', 'name'])
            ->assertJsonPath('name', 'Ane Bezeroa');

        $this->assertDatabaseHas('CUM_entities', ['name' => 'Ane Bezeroa']);
    }

    public function test_html_creation_opens_the_restore_conflict_for_a_trashed_name(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));
        $entity = Entity::factory()->create(['name' => 'Reused entity']);
        $entity->delete();

        $this->post(route('entities.store'), ['name' => $entity->name])
            ->assertRedirect(route('entities.index'))
            ->assertSessionHas('deleted_entity_conflict', [
                'id' => $entity->id,
                'name' => $entity->name,
            ]);

        $this->assertSame(0, Entity::query()->where('name', $entity->name)->count());
    }

    public function test_json_creation_requires_explicit_reuse_of_a_trashed_name(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));
        $entity = Entity::factory()->create(['name' => 'Reused entity']);
        $entity->delete();

        $this->postJson(route('entities.store'), ['name' => $entity->name])
            ->assertConflict()
            ->assertJsonPath('errors.name.0', __('basics13::messages.deleted_name_conflict', ['name' => $entity->name]));

        $this->postJson(route('entities.store'), [
            'name' => $entity->name,
            'reuse_deleted_name' => true,
        ])
            ->assertCreated()
            ->assertJsonPath('name', $entity->name);

        $this->assertSoftDeleted($entity);
        $this->assertSame(1, Entity::query()->where('name', $entity->name)->count());
    }

    public function test_store_converts_a_concurrent_duplicate_name_into_a_validation_error(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));
        $name = 'Concurrent entity';
        $this->createDuplicateNameAfterValidation($name);

        $this->postJson(route('entities.store'), ['name' => $name])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->assertDatabaseCount('CUM_entities', 1);
        $this->assertDatabaseHas('CUM_entities', ['name' => $name]);
    }

    public function test_update_converts_a_concurrent_duplicate_name_into_a_validation_error(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));
        $entity = Entity::factory()->create(['name' => 'Original entity']);
        $name = 'Concurrent entity';
        $this->createDuplicateNameAfterValidation($name);

        $this->putJson(route('entities.update', $entity), ['name' => $name])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');

        $this->assertDatabaseHas('CUM_entities', ['id' => $entity->id, 'name' => 'Original entity']);
        $this->assertDatabaseHas('CUM_entities', ['name' => $name]);
        $this->assertDatabaseCount('CUM_entities', 2);
    }

    /**
     * The soft delete has to happen inside a transaction even when no rule asks for one, because a
     * guard registered by an optional integration package takes its row lock in a transaction of its
     * own and would otherwise let go of it before the delete is written.
     */
    public function test_direct_model_delete_runs_the_soft_delete_inside_a_transaction(): void
    {
        $entity = Entity::factory()->create();
        $transactionLevel = null;

        DB::listen(static function (QueryExecuted $query) use (&$transactionLevel): void {
            if (str_starts_with($query->sql, 'update') && str_contains($query->sql, 'CUM_entities')) {
                $transactionLevel = DB::transactionLevel();
            }
        });

        $initialTransactionLevel = DB::transactionLevel();

        $entity->delete();

        $this->assertSame($initialTransactionLevel + 1, $transactionLevel);
        $this->assertSoftDeleted($entity);
    }

    public function test_invalid_entity_names_are_rejected(): void
    {
        $this->actingAs(User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()]));

        $this->postJson(route('entities.store'), ['name' => 'Abc'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
        $this->assertDatabaseCount('CUM_entities', 0);
    }

    public function test_creation_requires_authorization(): void
    {
        $user = User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()]);
        $this->actingAs($user);
        Gate::before(static fn(): bool => false);

        $this->post(route('entities.store'), [])->assertForbidden();

        $this->assertDatabaseCount('CUM_entities', 0);
    }

    public function test_entity_name_with_non_latin_characters_can_be_stored_and_searched(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));
        $name = 'Cliente Ñandú クライアント';

        $this->post(route('entities.store'), ['name' => $name])
            ->assertRedirect(route('entities.index'));

        $this->assertDatabaseHas('CUM_entities', ['name' => $name]);

        $this->get(route('entities.index', ['search' => $name]))
            ->assertOk()
            ->assertSee($name);
    }

    public function test_entity_search_is_kept_in_pagination_links(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));

        foreach (range(1, 30) as $index) {
            Entity::factory()->create(['name' => "Repeat entity {$index}"]);
        }

        $this->get(route('entities.index', ['search' => 'Repeat']))
            ->assertOk()
            ->assertSee('search=Repeat', false);
    }

    public function test_notes_are_saved_and_replaced_on_update(): void
    {
        $this->actingAs(User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()]));

        $this->post(route('entities.store'), [
            'name' => 'Notes entity',
            'notes' => 'Entity notes',
        ])->assertRedirect(route('entities.index'))
            ->assertSessionHasNoErrors();

        $entity = Entity::query()->sole();
        $this->assertSame('Entity notes', $entity->notes);

        $this->put(route('entities.update', $entity), [
            'name' => $entity->name,
            'notes' => 'Rewritten notes',
        ])->assertRedirect(route('entities.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Rewritten notes', $entity->fresh()->notes);
    }

    public function test_notes_are_optional_and_can_be_cleared(): void
    {
        $this->actingAs(User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()]));

        $this->post(route('entities.store'), ['name' => 'Notes entity'])
            ->assertRedirect(route('entities.index'))
            ->assertSessionHasNoErrors();

        $entity = Entity::query()->sole();
        $this->assertNull($entity->notes);

        $this->put(route('entities.update', $entity), [
            'name' => $entity->name,
            'notes' => 'Entity notes',
        ])->assertRedirect(route('entities.index'))
            ->assertSessionHasNoErrors();
        $this->assertSame('Entity notes', $entity->fresh()->notes);

        $this->put(route('entities.update', $entity), [
            'name' => $entity->name,
            'notes' => '',
        ])->assertRedirect(route('entities.index'))
            ->assertSessionHasNoErrors();

        $this->assertNull($entity->fresh()->notes);
    }

    public function test_notes_longer_than_the_configured_maximum_are_rejected(): void
    {
        $this->actingAs(User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()]));

        $this->from(route('entities.index'))
            ->post(route('entities.store'), [
                'name' => 'Notes entity',
                'notes' => str_repeat('a', MaxLength::longText() + 1),
            ])->assertRedirect(route('entities.index'))
            ->assertSessionHasErrors('notes');

        $this->assertDatabaseCount('CUM_entities', 0);
    }

    public function test_the_edit_form_is_bound_to_the_saved_notes(): void
    {
        $this->actingAs(User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()]));
        $entity = Entity::factory()->create(['notes' => 'Entity notes']);

        $this->get(route('entities.index'))
            ->assertOk()
            ->assertSee('x-model="form.notes"', false)
            ->assertSee('data-test="entity-notes"', false)
            ->assertViewHas('list', function (array $list) use ($entity): bool {
                return $list['rows'][0]['id'] === $entity->id
                    && $list['rows'][0]['editPayload']['notes'] === 'Entity notes';
            });
    }

    private function createDuplicateNameAfterValidation(string $name): void
    {
        $table = (new Entity)->getTable();
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
            Entity::factory()->create(['name' => $name]);
        });
    }
}
