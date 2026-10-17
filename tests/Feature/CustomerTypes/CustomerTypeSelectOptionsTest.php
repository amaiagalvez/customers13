<?php

namespace Customers13\Tests\Feature\CustomerTypes;

use App\Models\User;
use Customers13\Tests\TestCase;
use Basics13\Queries\ListQueryBase;
use Customers13\Models\CustomerType;
use Basics13\Support\Validation\MaxLength;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerTypeSelectOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_when_requesting_customer_type_options(): void
    {
        $this->get(route('customer-types.options'))
            ->assertRedirect(route('login'));
    }

    public function test_customer_type_options_are_searchable_and_limited_to_the_shared_page_size(): void
    {
        $this->actingAs($this->user());

        $total = ListQueryBase::PER_PAGE + 1;

        for ($index = 1; $index <= $total; $index++) {
            CustomerType::factory()->create([
                'name_eu' => sprintf('Search type %02d', $index),
            ]);
        }

        $this->getJson(route('customer-types.options', ['q' => 'Search type']))
            ->assertOk()
            ->assertJsonCount(ListQueryBase::PER_PAGE, 'results')
            ->assertJsonPath('results.0.text', 'Search type 01')
            ->assertJsonMissing(['text' => sprintf('Search type %02d', $total)]);
    }

    public function test_customer_type_options_only_include_active_types(): void
    {
        $this->actingAs($this->user());

        CustomerType::factory()->create(['name_eu' => 'Active select type']);
        CustomerType::factory()->archived()->create(['name_eu' => 'Inactive select type']);
        CustomerType::factory()->trashed()->create(['name_eu' => 'Deleted select type']);

        $this->getJson(route('customer-types.options', ['q' => 'select type']))
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.text', 'Active select type');
    }

    public function test_customer_type_options_match_a_type_by_any_of_its_languages(): void
    {
        $this->actingAs($this->user());

        CustomerType::factory()->create([
            'name_eu' => 'Mota bakarra',
            'name_es' => 'Nombre buscable',
            'name_fr' => 'Nom recherchable',
            'name_en' => 'Searchable name',
        ]);

        foreach (['Nombre buscable', 'Nom recherchable', 'Searchable name'] as $term) {
            $this->getJson(route('customer-types.options', ['q' => $term]))
                ->assertOk()
                ->assertJsonCount(1, 'results')
                ->assertJsonPath('results.0.text', 'Mota bakarra');
        }
    }

    public function test_customer_type_options_reject_search_terms_over_the_configured_maximum(): void
    {
        $this->actingAs($this->user());

        $term = str_repeat('a', MaxLength::string() + 1);

        $this->getJson(route('customer-types.options', ['q' => $term]))
            ->assertJsonValidationErrors('q');
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
