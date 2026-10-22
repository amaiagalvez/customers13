<?php

namespace Entities13\Tests\Feature\EntityTypes;

use App\Models\User;
use Entities13\Models\Entity;
use Entities13\Tests\TestCase;
use Entities13\Models\EntityType;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class EntityTypeArchivedTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_archived_types_are_not_shown_in_the_active_list(): void
    {
        $this->actingAs($this->user());
        EntityType::factory()->archived()->create(['name_eu' => 'Mota artxibatua']);
        EntityType::factory()->create(['name_eu' => 'Mota aktiboa']);

        $this->get(route('entity-types.index'))
            ->assertOk()
            ->assertDontSee('Mota artxibatua')
            ->assertSee('Mota aktiboa');
    }

    public function test_archived_types_are_searchable_by_any_of_their_names(): void
    {
        $this->actingAs($this->user());
        EntityType::factory()->archived()->create([
            'name_eu' => 'Alpha mota',
            'name_es' => 'Beta tipo',
            'name_fr' => 'Gamma type',
            'name_en' => 'Delta kind',
        ]);
        EntityType::factory()->archived()->create(['name_eu' => 'Zeta mota']);

        foreach (['Alpha', 'Beta', 'Gamma', 'Delta'] as $term) {
            $this->get(route('entity-types.archived.index', ['search' => $term]))
                ->assertOk()
                ->assertSee('Alpha mota')
                ->assertDontSee('Zeta mota');
        }
    }

    public function test_a_type_can_be_archived_and_activated(): void
    {
        $this->actingAs($this->user());
        $entityType = EntityType::factory()->create();

        $this->patch(route('entity-types.archive', $entityType))
            ->assertRedirect(route('entity-types.index'))
            ->assertSessionHas('status', __('basics13::messages.archived'));
        $this->assertFalse($entityType->refresh()->active);

        $this->patch(route('entity-types.archived.activate', $entityType))
            ->assertRedirect(route('entity-types.archived.index'))
            ->assertSessionHas('status', __('basics13::messages.activated'));
        $this->assertTrue($entityType->refresh()->active);
    }

    public function test_an_archived_type_held_by_a_entity_can_be_activated_again(): void
    {
        $this->actingAs($this->user());
        $entityType = EntityType::factory()->archived()->create();
        Entity::factory()->create()->entityTypes()->attach($entityType);

        $this->patch(route('entity-types.archived.activate', $entityType))
            ->assertRedirect(route('entity-types.archived.index'));

        $this->assertTrue($entityType->refresh()->active);
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
