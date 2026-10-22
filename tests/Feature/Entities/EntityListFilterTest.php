<?php

namespace Entities13\Tests\Feature\Entities;

use App\Models\User;
use Entities13\Models\Entity;
use Entities13\Tests\TestCase;
use Entities13\Models\EntityType;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EntityListFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_entity_type_filter_matches_entities_holding_any_selected_type(): void
    {
        [$strategic, $partner, $supplier] = EntityType::factory()->count(3)->create();

        Entity::factory()->create(['name' => 'Strategic only'])->entityTypes()->attach($strategic);
        Entity::factory()->create(['name' => 'Partner only'])->entityTypes()->attach($partner);
        Entity::factory()->create(['name' => 'Strategic and partner'])->entityTypes()->attach([$strategic->id, $partner->id]);
        Entity::factory()->create(['name' => 'Supplier only'])->entityTypes()->attach($supplier);

        $this->actingAs($this->verifiedUser())
            ->get(route('entities.index', ['filters' => ['entity_type_ids' => [
                'operator' => 'equals',
                'value' => [(string) $strategic->id, (string) $partner->id],
            ]]]))
            ->assertOk()
            ->assertSee('Strategic only')
            ->assertSee('Partner only')
            ->assertSee('Strategic and partner')
            ->assertDontSee('Supplier only')
            ->assertViewHas('entities', fn ($entities): bool => $entities->total() === 3);
    }

    public function test_entity_type_not_equals_filter_excludes_entities_holding_any_selected_type(): void
    {
        [$strategic, $partner] = EntityType::factory()->count(2)->create();

        Entity::factory()->create(['name' => 'Strategic only'])->entityTypes()->attach($strategic);
        Entity::factory()->create(['name' => 'Strategic and partner'])->entityTypes()->attach([$strategic->id, $partner->id]);
        Entity::factory()->create(['name' => 'Partner only'])->entityTypes()->attach($partner);
        Entity::factory()->create(['name' => 'Without types']);

        $this->actingAs($this->verifiedUser())
            ->get(route('entities.index', ['filters' => ['entity_type_ids' => [
                'operator' => 'not_equals',
                'value' => [(string) $strategic->id],
            ]]]))
            ->assertOk()
            ->assertSee('Partner only')
            ->assertSee('Without types')
            ->assertDontSee('Strategic only')
            ->assertDontSee('Strategic and partner');
    }

    public function test_filter_badges_link_to_the_list_without_their_filter(): void
    {
        // The trash, the one list that offers the status filter next to the others.
        Entity::factory()->trashed()->create(['name' => 'Holding entity']);
        $filters = [
            'name' => ['operator' => 'contains', 'value' => 'holding'],
            'active' => ['operator' => 'is_true'],
        ];

        $response = $this->actingAs($this->verifiedUser())
            ->get(route('entities.trash.index', ['search' => 'hold', 'page' => 2, 'filters' => $filters]))
            ->assertOk()
            ->assertSee(__('basics13::messages.operator_is_true'))
            ->assertDontSee('data-count-display', false);

        $withoutName = route('entities.trash.index', ['search' => 'hold', 'filters' => ['active' => $filters['active']]]);
        $withoutActive = route('entities.trash.index', ['search' => 'hold', 'filters' => ['name' => $filters['name']]]);

        $response->assertSee('href="'.e($withoutName).'"', false)
            ->assertSee('href="'.e($withoutActive).'"', false)
            ->assertSee('href="'.e(route('entities.trash.index', ['search' => 'hold'])).'"', false);
    }

    public function test_the_status_filter_is_only_offered_in_the_trash(): void
    {
        $this->actingAs($this->verifiedUser());

        $this->get(route('entities.index'))->assertDontSee('data-test="active-advanced-search-operator"', false);
        $this->get(route('entities.archived.index'))->assertDontSee('data-test="active-advanced-search-operator"', false);
        $this->get(route('entities.trash.index'))->assertSee('data-test="active-advanced-search-operator"', false);
    }

    public function test_state_tab_counts_ignore_the_active_filters(): void
    {
        Entity::factory()->create(['name' => 'Alpha']);
        Entity::factory()->create(['name' => 'Beta']);
        Entity::factory()->create(['name' => 'Gamma']);
        Entity::factory()->create(['name' => 'Archived', 'active' => false]);

        $this->actingAs($this->verifiedUser())
            ->get(route('entities.index', ['filters' => ['name' => ['operator' => 'contains', 'value' => 'Alpha']]]))
            ->assertOk()
            ->assertViewHas('entities', fn ($entities): bool => $entities->total() === 1)
            ->assertViewHas('list', fn (array $list): bool => array_column($list['tabs'], 'count') === [3, 1, 0]);
    }

    public function test_search_keeps_the_active_filters(): void
    {
        Entity::factory()->create(['name' => 'Holding entity']);
        $filters = ['name' => ['operator' => 'contains', 'value' => 'holding']];

        $this->actingAs($this->verifiedUser())
            ->get(route('entities.index', ['search' => 'hold', 'filters' => $filters]))
            ->assertOk()
            // The native submit carries them as hidden inputs, and clearing the search keeps them.
            ->assertSee('<input type="hidden" name="filters[name][operator]" value="contains">', false)
            ->assertSee('<input type="hidden" name="filters[name][value]" value="holding">', false)
            ->assertSee('href="'.e(route('entities.index', ['filters' => $filters])).'"', false);
    }

    public function test_a_list_emptied_by_filters_says_nothing_matched_and_offers_no_create_shortcut(): void
    {
        Entity::factory()->create(['name' => 'Alpha']);

        $this->actingAs($this->verifiedUser())
            ->get(route('entities.index', ['filters' => ['name' => ['operator' => 'contains', 'value' => 'Nothing like it']]]))
            ->assertOk()
            ->assertSee(__('basics13::messages.no_matching_records'))
            ->assertDontSee('data-test="entity-empty-create"', false);
    }

    private function verifiedUser(): User
    {
        return $this->admin(User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()]));
    }
}
