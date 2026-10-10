<?php

namespace Customers13\Tests\Feature\Customers;

use App\Models\User;
use App\Models\Project;
use Customers13\Tests\TestCase;
use Customers13\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_customer_list_shows_its_empty_state(): void
    {
        $this->actingAs($user = User::factory()->create())
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee(__('No customers yet.'));
    }

    public function test_authenticated_users_can_create_update_and_delete_customers(): void
    {
        $this->actingAs($user = User::factory()->create());

        $response = $this->post(route('customers.store'), ['name' => 'Ane Bezeroa']);
        $response->assertRedirect(route('customers.index'))
            ->assertSessionHas('status', __('Record created successfully.'));

        $customer = Customer::query()->firstOrFail();
        $this->assertSame('Ane Bezeroa', $customer->name);

        $this->get(route('customers.index'))
            ->assertOk()
            ->assertSee('customer-create')
            ->assertSee('aria-labelledby="customer-form-heading"', false)
            ->assertSee('id="customer-form-heading"', false)
            ->assertSee('customer-edit-'.$customer->id);

        $this->put(route('customers.update', $customer), ['name' => 'Jon Bezeroa'])
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('status', __('Record updated successfully.'));
        $this->assertDatabaseHas('CUM_customers', ['id' => $customer->id, 'name' => 'Jon Bezeroa']);

        $this->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('status', __('Record moved to trash.'));
        $this->assertSoftDeleted($customer);
    }

    public function test_customer_with_a_project_cannot_be_deleted(): void
    {
        $this->actingAs(User::factory()->create());
        $customer = Customer::factory()->create();
        $project = Project::factory()->for($customer)->create();

        $this->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('error', __('Cannot be deleted while it has related records.'));

        $this->assertNotSoftDeleted($customer);
        $this->assertModelExists($project);
    }
}
