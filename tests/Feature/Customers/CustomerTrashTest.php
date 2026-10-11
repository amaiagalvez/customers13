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
        $this->actingAs(User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()]));
        Customer::factory()->create(['name' => 'Matching customer']);
        Customer::factory()->create(['name' => 'Other customer']);

        $response = $this->get(route('customers.index', ['search' => 'Matching']));
        $response->assertOk();
        $response->assertSeeInOrder(['data-test="customer-active-link"', '>2</span>'], false);
    }

    public function test_trash_list_searches_deleted_customers_without_showing_live_records(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));

        Customer::factory()->trashed()->create(['name' => 'Matching deleted customer']);
        Customer::factory()->trashed()->create(['name' => 'Other deleted customer']);
        Customer::factory()->create(['name' => 'Matching active customer']);

        $this->get(route('customers.trash.index', ['search' => 'Matching']))
            ->assertOk()
            ->assertSee('Matching deleted customer')
            ->assertDontSee('Other deleted customer')
            ->assertDontSee('Matching active customer');
    }

    public function test_customer_can_be_restored_and_permanently_deleted(): void
    {
        $this->actingAs(User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()]));
        $customer = Customer::factory()->create();
        $customer->delete();

        $this->patch(route('customers.trash.restore', $customer->id))
            ->assertRedirect(route('customers.trash.index'));
        $this->assertNotSoftDeleted($customer);

        $customer->refresh()->delete();
        $this->delete(route('customers.trash.destroy', $customer->id))
            ->assertRedirect(route('customers.trash.index'));
        $this->assertModelMissing($customer);
    }

    public function test_restore_is_rejected_when_an_active_customer_has_reused_the_name(): void
    {
        $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));
        $trashedCustomer = Customer::factory()->create(['name' => 'Reused customer']);
        $trashedCustomer->delete();
        $activeCustomer = Customer::factory()->create(['name' => 'Reused customer']);

        $this->patch(route('customers.trash.restore', $trashedCustomer->id))
            ->assertRedirect(route('customers.trash.index'))
            ->assertSessionHas('error', __('basics13::messages.cannot_restore_name_taken'));

        $this->assertSoftDeleted($trashedCustomer);
        $this->assertModelExists($activeCustomer);
    }
}
