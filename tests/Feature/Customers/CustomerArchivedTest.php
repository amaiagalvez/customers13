<?php

namespace Customers13\Tests\Feature\Customers;

use App\Models\User;
use Customers13\Tests\TestCase;
use Customers13\Models\Customer;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class CustomerArchivedTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_archived_customers_are_not_shown_in_the_active_list(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));

        Customer::factory()->archived()->create(['name' => 'Dormant customer']);
        Customer::factory()->create(['name' => 'Active customer']);

        $this->get(route('customers.index'))
            ->assertOk()
            ->assertDontSee('Dormant customer');
    }

    public function test_archived_customers_are_searchable_by_name(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));

        $this->travelTo('2026-10-02 12:30:00');
        $updatedCustomer = Customer::factory()->archived()->create(['name' => 'Alpha Dormant']);
        Customer::factory()->archived()->create(['name' => 'Beta Dormant']);
        Customer::factory()->create(['name' => 'Alpha Active']);
        $this->travelTo('2026-07-15 12:30:00');
        $updatedCustomer->touch();

        $this->get(route('customers.archived.index', ['search' => 'Alpha']))
            ->assertOk()
            ->assertSee('Alpha Dormant')
            ->assertDontSee('Beta Dormant')
            ->assertDontSee('Alpha Active');
    }

    public function test_customer_can_be_archived_and_reactivated(): void
    {
        $customer = Customer::factory()->create();
        $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));

        $this->patch(route('customers.archive', $customer))
            ->assertRedirect(route('customers.index'));
        $this->assertFalse($customer->refresh()->active);

        $this->patch(route('customers.archived.activate', $customer))
            ->assertRedirect(route('customers.archived.index'));
        $this->assertTrue($customer->refresh()->active);
    }

    public function test_restoring_a_trashed_archived_customer_preserves_its_archived_state(): void
    {
        $customer = Customer::factory()->archived()->trashed()->create();
        $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));

        $this->patch(route('customers.trash.restore', $customer->id))
            ->assertRedirect(route('customers.trash.index'));

        $this->assertNotSoftDeleted($customer);
        $this->assertFalse($customer->refresh()->active);
    }
}
