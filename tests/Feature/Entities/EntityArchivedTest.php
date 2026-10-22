<?php

namespace Entities13\Tests\Feature\Entities;

use App\Models\User;
use Entities13\Models\Entity;
use Entities13\Tests\TestCase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class EntityArchivedTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_archived_entities_are_not_shown_in_the_active_list(): void
    {
        $this->actingAs($this->admin(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ])));

        Entity::factory()->archived()->create(['name' => 'Dormant entity']);
        Entity::factory()->create(['name' => 'Active entity']);

        $this->get(route('entities.index'))
            ->assertOk()
            ->assertDontSee('Dormant entity');
    }

    public function test_archived_entities_are_searchable_by_name(): void
    {
        $this->actingAs($this->admin(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ])));

        $this->travelTo('2026-10-02 12:30:00');
        $updatedEntity = Entity::factory()->archived()->create(['name' => 'Alpha Dormant']);
        Entity::factory()->archived()->create(['name' => 'Beta Dormant']);
        Entity::factory()->create(['name' => 'Alpha Active']);
        $this->travelTo('2026-07-15 12:30:00');
        $updatedEntity->touch();

        $this->get(route('entities.archived.index', ['search' => 'Alpha']))
            ->assertOk()
            ->assertSee('Alpha Dormant')
            ->assertDontSee('Beta Dormant')
            ->assertDontSee('Alpha Active');
    }

    public function test_entity_can_be_archived_and_reactivated(): void
    {
        $entity = Entity::factory()->create();
        $this->actingAs($this->admin(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ])));

        $this->patch(route('entities.archive', $entity))
            ->assertRedirect(route('entities.index'));
        $this->assertFalse($entity->refresh()->active);

        $this->patch(route('entities.archived.activate', $entity))
            ->assertRedirect(route('entities.archived.index'));
        $this->assertTrue($entity->refresh()->active);
    }

    public function test_restoring_a_trashed_archived_entity_preserves_its_archived_state(): void
    {
        $entity = Entity::factory()->archived()->trashed()->create();
        $this->actingAs($this->admin(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ])));

        $this->patch(route('entities.trash.restore', $entity->id))
            ->assertRedirect(route('entities.trash.index'));

        $this->assertNotSoftDeleted($entity);
        $this->assertFalse($entity->refresh()->active);
    }
}
