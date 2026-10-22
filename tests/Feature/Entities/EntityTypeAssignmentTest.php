<?php

namespace Entities13\Tests\Feature\Entities;

use App\Models\User;
use Entities13\Models\Entity;
use Entities13\Tests\TestCase;
use Entities13\Models\EntityType;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EntityTypeAssignmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_entity_can_hold_several_types(): void
    {
        $this->actingAs($this->user());
        $first = EntityType::factory()->create(['name_eu' => 'Mota bat']);
        $second = EntityType::factory()->create(['name_eu' => 'Mota bi']);

        $this->post(route('entities.store'), [
            'name' => 'Bezeroa',
            'entity_type_ids' => [$first->id, $second->id],
        ])
            ->assertRedirect(route('entities.index'))
            ->assertSessionHasNoErrors();

        $entity = Entity::query()->sole();
        $this->assertSame(
            [$first->id, $second->id],
            $entity->entityTypes()->orderBy('entity_type_id')->pluck('entity_type_id')->all(),
        );
    }

    public function test_the_entity_list_shows_the_types_of_each_entity(): void
    {
        $this->actingAs($this->user());
        $entityType = EntityType::factory()->create(['name_eu' => 'Mota ikusgai']);
        $entity = Entity::factory()->create(['name' => 'Bezeroa']);
        $entity->entityTypes()->attach($entityType);

        $this->get(route('entities.index'))
            ->assertOk()
            ->assertSee(__('Types'))
            ->assertSee('Mota ikusgai')
            ->assertSee('data-test="entity-types-'.$entity->id.'"', false);
    }

    public function test_the_edit_payload_carries_the_type_ids_as_strings(): void
    {
        $this->actingAs($this->user());
        $first = EntityType::factory()->create();
        $second = EntityType::factory()->create();
        $entity = Entity::factory()->create(['name' => 'Bezeroa']);
        $entity->entityTypes()->attach([$first->id, $second->id]);

        $this->get(route('entities.index'))
            ->assertOk()
            ->assertViewHas('list', function (array $list) use ($first, $second): bool {
                return $list['rows'][0]['editPayload']['entity_type_ids'] === [
                    (string) $first->id,
                    (string) $second->id,
                ];
            });
    }

    public function test_the_entity_form_offers_every_type_that_can_be_picked(): void
    {
        $this->actingAs($this->user());
        EntityType::factory()->create(['name_eu' => 'Mota aktiboa']);
        EntityType::factory()->archived()->create(['name_eu' => 'Mota artxibatua']);
        EntityType::factory()->trashed()->create(['name_eu' => 'Mota ezabatua']);

        $this->get(route('entities.index'))
            ->assertOk()
            ->assertSee('data-test="entity-type-ids"', false)
            ->assertSee('Mota aktiboa')
            ->assertSee('Mota artxibatua')
            ->assertDontSee('Mota ezabatua');
    }

    public function test_updating_a_entity_replaces_the_types_it_holds(): void
    {
        $this->actingAs($this->user());
        $held = EntityType::factory()->create();
        $dropped = EntityType::factory()->create();
        $added = EntityType::factory()->create();
        $entity = Entity::factory()->create(['name' => 'Bezeroa']);
        $entity->entityTypes()->attach([$held->id, $dropped->id]);

        $this->put(route('entities.update', $entity), [
            'name' => 'Bezeroa',
            'entity_type_ids' => [$held->id, $added->id],
        ])
            ->assertRedirect(route('entities.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(
            [$held->id, $added->id],
            $entity->entityTypes()->orderBy('entity_type_id')->pluck('entity_type_id')->all(),
        );
    }

    public function test_an_update_that_posts_no_types_empties_the_set(): void
    {
        $this->actingAs($this->user());
        $entityType = EntityType::factory()->create();
        $entity = Entity::factory()->create(['name' => 'Bezeroa']);
        $entity->entityTypes()->attach($entityType);

        $this->put(route('entities.update', $entity), ['name' => 'Bezeroa'])
            ->assertRedirect(route('entities.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(0, DB::table('ENT_entity_entity_type')
            ->where('entity_id', $entity->id)->count());
    }

    public function test_an_archived_type_the_entity_already_holds_stays_assigned(): void
    {
        $this->actingAs($this->user());
        $entityType = EntityType::factory()->create();
        $entity = Entity::factory()->create(['name' => 'Bezeroa']);
        $entity->entityTypes()->attach($entityType);

        $this->patch(route('entity-types.archive', $entityType))->assertRedirect();

        $this->put(route('entities.update', $entity), [
            'name' => 'Bezeroa',
            'entity_type_ids' => [$entityType->id],
        ])
            ->assertRedirect(route('entities.index'))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('ENT_entity_entity_type', [
            'entity_id' => $entity->id,
            'entity_type_id' => $entityType->id,
        ]);
    }

    public function test_an_archived_type_cannot_be_assigned_to_a_entity_that_does_not_hold_it(): void
    {
        $this->actingAs($this->user());
        $entityType = EntityType::factory()->archived()->create();

        $this->from(route('entities.index'))
            ->post(route('entities.store'), [
                'name' => 'Bezeroa',
                'entity_type_ids' => [$entityType->id],
            ])
            ->assertRedirect(route('entities.index'))
            ->assertSessionHasErrors('entity_type_ids.0');

        $this->assertDatabaseCount('ENT_entities', 0);
    }

    public function test_a_trashed_type_cannot_be_assigned(): void
    {
        $this->actingAs($this->user());
        $entityType = EntityType::factory()->trashed()->create();

        $this->from(route('entities.index'))
            ->post(route('entities.store'), [
                'name' => 'Bezeroa',
                'entity_type_ids' => [$entityType->id],
            ])
            ->assertRedirect(route('entities.index'))
            ->assertSessionHasErrors('entity_type_ids.0');

        $this->assertDatabaseCount('ENT_entities', 0);
    }

    public function test_a_type_trashed_after_validation_is_not_assigned(): void
    {
        $this->actingAs($this->user());
        $entityType = EntityType::factory()->create();
        Entity::creating(static function () use ($entityType): void {
            $entityType->delete();
        });

        // Reported on the field like any other unavailable type, not as a server error.
        $this->from(route('entities.index'))
            ->post(route('entities.store'), [
                'name' => 'Bezeroa',
                'entity_type_ids' => [$entityType->id],
            ])
            ->assertRedirect(route('entities.index'))
            ->assertSessionHasErrors('entity_type_ids');

        $this->assertDatabaseCount('ENT_entities', 0);
        $this->assertNotSoftDeleted($entityType);
    }

    public function test_a_type_that_does_not_exist_cannot_be_assigned(): void
    {
        $this->actingAs($this->user());

        $this->from(route('entities.index'))
            ->post(route('entities.store'), [
                'name' => 'Bezeroa',
                'entity_type_ids' => [999999],
            ])
            ->assertRedirect(route('entities.index'))
            ->assertSessionHasErrors('entity_type_ids.0');

        $this->assertDatabaseCount('ENT_entities', 0);
    }

    public function test_deleting_a_entity_keeps_its_types_reserved(): void
    {
        $this->actingAs($this->user());
        $entityType = EntityType::factory()->create();
        $entity = Entity::factory()->create();
        $entity->entityTypes()->attach($entityType);

        $this->delete(route('entities.destroy', $entity))
            ->assertRedirect(route('entities.index'));

        $this->assertSoftDeleted($entity);
        $this->delete(route('entity-types.destroy', $entityType))
            ->assertSessionHas('error', __('basics13::messages.cannot_delete_related'));
        $this->assertNotSoftDeleted($entityType);
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
