<?php

namespace Customers13\Tests\Feature;

use Customers13\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTrashTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_search_keeps_the_global_active_tab_count(): void
    {
        $this->actingAs(User::factory()->create());
        Customer::factory()->create(['name' => 'Matching customer']);
        Customer::factory()->create(['name' => 'Other customer']);

        $response = $this->get(route('customers13.customers.index', ['search' => 'Matching']));
        $response->assertOk();
        $response->assertSeeInOrder(['data-test="customer-active-link"', '>2</span>'], false);
    }
}
