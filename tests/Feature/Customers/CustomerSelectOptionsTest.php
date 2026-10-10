<?php

namespace Customers13\Tests\Feature\Customers;

use App\Models\User;
use Customers13\Tests\TestCase;
use Customers13\Models\Customer;
use Basics13\Queries\ListQueryBase;
use Basics13\Support\Validation\MaxLength;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerSelectOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_when_requesting_customer_options(): void
    {
        $this->get(route('customers.options'))
            ->assertRedirect(route('login'));
    }

    public function test_customer_options_are_searchable_and_limited_to_the_shared_page_size(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));

        $total = ListQueryBase::PER_PAGE + 1;

        for ($index = 1; $index <= $total; $index++) {
            Customer::factory()->create([
                'name' => sprintf('Search result %02d', $index),
            ]);
        }

        $this->getJson(route('customers.options', ['q' => 'Search result']))
            ->assertOk()
            ->assertJsonCount(ListQueryBase::PER_PAGE, 'results')
            ->assertJsonPath('results.0.text', 'Search result 01')
            ->assertJsonMissing(['text' => sprintf('Search result %02d', $total)]);
    }

    public function test_customer_options_only_include_active_customers(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));

        Customer::factory()->create(['name' => 'Active select customer']);
        Customer::factory()->archived()->create(['name' => 'Inactive select customer']);
        Customer::factory()->trashed()->create(['name' => 'Deleted select customer']);

        $this->getJson(route('customers.options', ['q' => 'select customer']))
            ->assertOk()
            ->assertJsonCount(1, 'results')
            ->assertJsonPath('results.0.text', 'Active select customer');
    }

    public function test_customer_options_reject_search_terms_over_the_configured_maximum(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));

        $term = str_repeat('a', MaxLength::string() + 1);

        $this->getJson(route('customers.options', ['q' => $term]))
            ->assertJsonValidationErrors('q');
    }
}
