<?php

namespace Entities13\Tests\Feature\EntityTypes;

use App\Models\User;
use Entities13\Models\Entity;
use Entities13\Tests\TestCase;
use Entities13\Models\EntityType;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class EntityTypeTrashTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('entity-types.trash.index'))->assertRedirect(route('login'));
    }

    public function test_trashed_types_are_listed_in_the_trash_and_not_in_the_active_list(): void
    {
        $this->actingAs($this->user());
        EntityType::factory()->create(['name_eu' => 'Mota aktiboa']);
        EntityType::factory()->trashed()->create(['name_eu' => 'Mota ezabatua']);

        $this->get(route('entity-types.index'))
            ->assertOk()
            ->assertSee('Mota aktiboa')
            ->assertDontSee('Mota ezabatua');

        $this->get(route('entity-types.trash.index'))
            ->assertOk()
            ->assertSee('Mota ezabatua')
            ->assertDontSee('Mota aktiboa');
    }

    public function test_a_trashed_type_can_be_restored(): void
    {
        $this->actingAs($this->user());
        $entityType = EntityType::factory()->trashed()->create();

        $this->patch(route('entity-types.trash.restore', $entityType->id))
            ->assertRedirect(route('entity-types.trash.index'))
            ->assertSessionHas('status', __('basics13::messages.restored'));

        $this->assertNotSoftDeleted($entityType);
    }

    public function test_restoring_a_trashed_archived_type_preserves_its_archived_state(): void
    {
        $this->actingAs($this->user());
        $entityType = EntityType::factory()->archived()->trashed()->create();

        $this->patch(route('entity-types.trash.restore', $entityType->id))
            ->assertRedirect(route('entity-types.trash.index'));

        $this->assertNotSoftDeleted($entityType);
        $this->assertFalse($entityType->refresh()->active);
    }

    public function test_a_trashed_type_cannot_be_restored_while_a_live_type_uses_any_of_its_names(): void
    {
        $this->actingAs($this->user());
        $entityType = EntityType::factory()->trashed()->create([
            'name_eu' => 'Mota zaharkitua',
            'name_es' => 'Tipo en la papelera',
        ]);

        // The euskera name is free, but the Spanish one is taken: a type is named in four languages
        // and restoring it puts all four back at once.
        EntityType::factory()->create([
            'name_eu' => 'Beste mota bat',
            'name_es' => 'Tipo en la papelera',
        ]);

        $this->patch(route('entity-types.trash.restore', $entityType->id))
            ->assertRedirect(route('entity-types.trash.index'))
            ->assertSessionHas('error', __('basics13::messages.cannot_restore_name_taken'));

        $this->assertSoftDeleted($entityType);
    }

    public function test_a_restore_that_resolves_the_name_conflict_reports_it(): void
    {
        $this->actingAs($this->user());
        $entityType = EntityType::factory()->trashed()->create(['name_eu' => 'Mota zaharkitua']);

        $this->patch(route('entity-types.trash.restore', $entityType->id), [
            'resolve_name_conflict' => true,
        ])
            ->assertRedirect(route('entity-types.trash.index'))
            ->assertSessionHas('status', __('basics13::messages.restored_no_new_record'));

        $this->assertNotSoftDeleted($entityType);
    }

    public function test_a_trashed_type_no_entity_points_at_can_be_permanently_deleted(): void
    {
        $this->actingAs($this->user());
        $entityType = EntityType::factory()->trashed()->create();

        $this->delete(route('entity-types.trash.destroy', $entityType->id))
            ->assertRedirect(route('entity-types.trash.index'))
            ->assertSessionHas('status', __('basics13::messages.permanently_deleted'));

        $this->assertDatabaseMissing('ENT_entity_types', ['id' => $entityType->id]);
    }

    public function test_a_force_delete_is_refused_by_the_pivot_foreign_key_while_a_entity_points_at_the_type(): void
    {
        $this->actingAs($this->user());
        $entityType = EntityType::factory()->trashed()->create();
        // A trashed type a entity still points at is not reachable through the application (the
        // soft delete is refused first), so the pivot row is written directly to exercise the
        // foreign key that backstops the guard.
        Entity::factory()->create()->entityTypes()->attach($entityType);

        $this->delete(route('entity-types.trash.destroy', $entityType->id))
            ->assertRedirect(route('entity-types.trash.index'))
            ->assertSessionHas('error', __('basics13::messages.cannot_force_delete_related'));

        $this->assertDatabaseHas('ENT_entity_types', ['id' => $entityType->id]);
    }

    public function test_the_trash_list_blocks_the_permanent_delete_while_a_entity_points_at_the_type(): void
    {
        $this->actingAs($this->user());
        $entityType = EntityType::factory()->trashed()->create();
        Entity::factory()->create()->entityTypes()->attach($entityType);

        $this->get(route('entity-types.trash.index'))
            ->assertOk()
            ->assertSee('data-test="entity-type-force-delete-blocked-'.$entityType->id.'"', false)
            ->assertDontSee('data-test="entity-type-force-delete-'.$entityType->id.'"', false)
            ->assertSee(__('basics13::messages.cannot_force_delete_related'));
    }

    #[Group('mariadb')]
    public function test_restore_converts_a_concurrent_duplicate_name_to_a_conflict(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('The concurrent restore guard requires MySQL row locks.');
        }

        $this->actingAs($this->user());
        $name = 'Concurrent Restore EntityType';
        $deleted = EntityType::factory()->trashed()->create([
            'name_eu' => $name,
            'name_es' => $name,
            'name_fr' => $name,
            'name_en' => $name,
        ]);

        $competitorCreated = false;

        DB::listen(static function (QueryExecuted $query) use ($deleted, $name, &$competitorCreated): void {
            $sql = strtolower(ltrim($query->sql));
            $bindings = $query->bindings;

            if (
                $competitorCreated
                || ! str_starts_with($sql, 'select')
                || ! str_contains($sql, 'ent_entity_types')
                || ! in_array($deleted->name_eu, $bindings, true)
            ) {
                return;
            }

            $competitorCreated = true;
            EntityType::factory()->create([
                'name_eu' => $name,
                'name_es' => $name,
                'name_fr' => $name,
                'name_en' => $name,
            ]);
        });

        $this->patch(route('entity-types.trash.restore', $deleted->id))
            ->assertRedirect(route('entity-types.trash.index'))
            ->assertSessionHas('error', __('basics13::messages.cannot_restore_name_taken'));

        $this->assertSoftDeleted($deleted);
        $this->assertDatabaseHas('ENT_entity_types', ['name_eu' => $name, 'deleted_at' => null]);
    }

    private function user(): User
    {
        return $this->admin(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));
    }
}
