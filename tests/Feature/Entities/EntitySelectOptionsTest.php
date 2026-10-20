<?php

namespace Entities13\Tests\Feature\Entities;

use App\Models\User;
use Entities13\Models\Entity;
use Entities13\Tests\TestCase;
use Basics13\Queries\ListQueryBase;
use Basics13\Support\Validation\MaxLength;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EntitySelectOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_when_requesting_entity_options(): void
    {
        $this->get(route('entities.options'))
            ->assertRedirect(route('login'));
    }

    public function test_entity_options_are_searchable_and_limited_to_the_shared_page_size(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));

        $total = ListQueryBase::PER_PAGE + 1;

        for ($index = 1; $index <= $total; $index++) {
            Entity::factory()->create([
                'name' => sprintf('Search result %02d', $index),
            ]);
        }

        $this->getJson(route('entities.options', ['q' => 'Search result']))
            ->assertOk()
            ->assertJsonCount(ListQueryBase::PER_PAGE, 'results')
            ->assertJsonPath('results.0.text', 'Search result 01')
            ->assertJsonMissing(['text' => sprintf('Search result %02d', $total)]);
    }

    public function test_entity_options_only_include_active_entities(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));

        Entity::factory()->create(['name' => 'Active select entity']);
        Entity::factory()->archived()->create(['name' => 'Inactive select entity']);
        Entity::factory()->trashed()->create(['name' => 'Deleted select entity']);

        $this->getJson(route('entities.options', ['q' => 'select entity']))
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.text', 'Active select entity');
    }

    public function test_entity_options_reject_search_terms_over_the_configured_maximum(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));

        $term = str_repeat('a', MaxLength::string() + 1);

        $this->getJson(route('entities.options', ['q' => $term]))
            ->assertJsonValidationErrors('q');
    }
}
