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

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('customers.index'))->assertRedirect(route('login'));
        $this->post(route('customers.store'), ['name' => 'Guest customer'])
            ->assertRedirect(route('login'));
        $this->assertDatabaseCount('CUM_customers', 0);
    }

    public function test_unverified_users_cannot_create_customers(): void
    {
        $user = User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused']);

        $this->actingAs($user)
            ->post(route('customers.store'), ['name' => 'Unverified customer'])
            ->assertRedirect(route('verification.notice'));
        $this->assertDatabaseCount('CUM_customers', 0);
    }

    public function test_empty_customer_list_shows_its_empty_state(): void
    {
        $this->actingAs(User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()]))
            ->get(route('customers.index'))
            ->assertOk()
            ->assertSee(__('No customers yet.'));
    }

    public function test_authenticated_users_can_create_update_and_delete_customers(): void
    {
        $this->actingAs(User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()]));

        $response = $this->post(route('customers.store'), ['name' => 'Ane Bezeroa']);
        $response->assertRedirect(route('customers.index'))
            ->assertSessionHas('status', __('customers13::messages.created'));

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
            ->assertSessionHas('status', __('customers13::messages.updated'));
        $this->assertDatabaseHas('CUM_customers', ['id' => $customer->id, 'name' => 'Jon Bezeroa']);

        $this->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('status', __('customers13::messages.moved_to_trash'));
        $this->assertSoftDeleted($customer);
    }

    public function test_customer_with_a_project_cannot_be_deleted(): void
    {
        $this->actingAs(User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()]));
        $customer = Customer::factory()->create();
        $project = Project::create(['name' => 'Test project', 'customer_id' => $customer->id]);

        $this->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('error', __('Cannot be deleted while it has related records.'));

        $this->assertNotSoftDeleted($customer);
        $this->assertModelExists($project);
    }

    public function test_customer_with_a_trashed_project_cannot_be_deleted(): void
    {
        $this->actingAs(User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()]));
        $customer = Customer::factory()->create();
        $project = Project::create(['name' => 'Trashed project', 'customer_id' => $customer->id]);
        $project->delete();

        $this->delete(route('customers.destroy', $customer))
            ->assertRedirect(route('customers.index'))
            ->assertSessionHas('error', __('Cannot be deleted while it has related records.'));

        $this->assertNotSoftDeleted($customer);
        $this->assertSoftDeleted($project);
    }

    public function test_invalid_customer_names_are_rejected(): void
    {
        $this->actingAs(User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()]));

        $this->postJson(route('customers.store'), ['name' => 'Abc'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('name');
        $this->assertDatabaseCount('CUM_customers', 0);
    }
}
