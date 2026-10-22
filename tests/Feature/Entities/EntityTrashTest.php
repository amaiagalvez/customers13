<?php

namespace Entities13\Tests\Feature\Entities;

use App\Models\User;
use Entities13\Models\Entity;
use Entities13\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EntityTrashTest extends TestCase
{
    use RefreshDatabase;

    public function test_entity_search_keeps_the_global_active_tab_count(): void
    {
        $this->actingAs($this->admin(User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()])));
        Entity::factory()->create(['name' => 'Matching entity']);
        Entity::factory()->create(['name' => 'Other entity']);

        $response = $this->get(route('entities.index', ['search' => 'Matching']));
        $response->assertOk();
        $response->assertSeeInOrder(['data-test="entity-active-link"', '>2</span>'], false);
    }

    public function test_trash_list_searches_deleted_entities_without_showing_live_records(): void
    {
        $this->actingAs($this->admin(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ])));

        Entity::factory()->trashed()->create(['name' => 'Matching deleted entity']);
        Entity::factory()->trashed()->create(['name' => 'Other deleted entity']);
        Entity::factory()->create(['name' => 'Matching active entity']);

        $this->get(route('entities.trash.index', ['search' => 'Matching']))
            ->assertOk()
            ->assertSee('Matching deleted entity')
            ->assertDontSee('Other deleted entity')
            ->assertDontSee('Matching active entity');
    }

    public function test_entity_can_be_restored_and_permanently_deleted(): void
    {
        $this->actingAs($this->admin(User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()])));
        $entity = Entity::factory()->create();
        $entity->delete();

        $this->patch(route('entities.trash.restore', $entity->id))
            ->assertRedirect(route('entities.trash.index'));
        $this->assertNotSoftDeleted($entity);

        $entity->refresh()->delete();
        $this->delete(route('entities.trash.destroy', $entity->id))
            ->assertRedirect(route('entities.trash.index'));
        $this->assertModelMissing($entity);
    }

    public function test_restore_is_rejected_when_an_active_entity_has_reused_the_name(): void
    {
        $this->actingAs($this->admin(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ])));
        $trashedEntity = Entity::factory()->create(['name' => 'Reused entity']);
        $trashedEntity->delete();
        $activeEntity = Entity::factory()->create(['name' => 'Reused entity']);

        $this->patch(route('entities.trash.restore', $trashedEntity->id))
            ->assertRedirect(route('entities.trash.index'))
            ->assertSessionHas('error', __('basics13::messages.cannot_restore_name_taken'));

        $this->assertSoftDeleted($trashedEntity);
        $this->assertModelExists($activeEntity);
    }
}
