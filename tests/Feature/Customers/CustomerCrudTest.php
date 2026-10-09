<?php

namespace Customers13\Tests\Feature;

use Tests\TestCase;
use Customers13\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

class CustomerCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_customer_list_shows_its_empty_state(): void
    {
        $this->actingAs($user = User::factory()->create())
            ->get(route('customers13.customers.index'))
            ->assertOk()
            ->assertSee(__('No customers yet.'));
    }

    public function test_authenticated_users_can_create_update_and_delete_customers(): void
    {
        $this->actingAs($user = User::factory()->create());

        $response = $this->post(route('customers13.customers.store'), ['name' => 'Ane Bezeroa']);
        $response->assertRedirect(route('customers13.customers.index'))
            ->assertSessionHas('status', __('Record created successfully.'));

        $customer = Customer::query()->firstOrFail();
        $this->assertSame('Ane Bezeroa', $customer->name);

        $this->get(route('customers13.customers.index'))
            ->assertOk()
            ->assertSee('customer-create')
            ->assertSee('aria-labelledby="customer-form-heading"', false)
            ->assertSee('id="customer-form-heading"', false)
            ->assertSee('customer-edit-'.$customer->id);

        $this->put(route('customers13.customers.update', $customer), ['name' => 'Jon Bezeroa'])
            ->assertRedirect(route('customers13.customers.index'))
            ->assertSessionHas('status', __('Record updated successfully.'));
        $this->assertDatabaseHas('CUM_customers', ['id' => $customer->id, 'name' => 'Jon Bezeroa']);

        $this->delete(route('customers13.customers.destroy', $customer))
            ->assertRedirect(route('customers13.customers.index'))
            ->assertSessionHas('status', __('Record moved to trash.'));
        $this->assertSoftDeleted($customer);
    }

    public function test_customer_with_a_project_cannot_be_deleted(): void
    {
        $this->actingAs(User::factory()->create());
        $customer = Customer::factory()->create();
        $project = \Customers13\Models\Project::factory()->for($customer)->create();

        $this->delete(route('customers13.customers.destroy', $customer))
            ->assertRedirect(route('customers13.customers.index'))
            ->assertSessionHas('error', __('Cannot be deleted while it has related records.')));

        $this->assertNotSoftDeleted($customer);
        $this->assertModelExists($project);
    }
}