<?php

namespace Customers13\Tests\Feature\Customers;

use App\Models\User;
use Customers13\Tests\TestCase;
use Customers13\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerTrashTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_search_keeps_the_global_active_tab_count(): void
    {
        $this->actingAs(User::factory()->create());
        Customer::factory()->create(['name' => 'Matching customer']);
        Customer::factory()->create(['name' => 'Other customer']);

        $response = $this->get(route('customers.index', ['search' => 'Matching']));
        $response->assertOk();
        $response->assertSeeInOrder(['data-test="customer-active-link"', '>2</span>'], false);
    }
}
