<?php

namespace Customers13\Tests\Feature\Customers;

use App\Models\User;
use Projects13\Models\Epic;
use Projects13\Models\Project;
use Customers13\Tests\TestCase;
use Customers13\Models\Customer;
use Projects13\Models\EpicComment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Customers13\Queries\Customers\CustomerListQuery;

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

    public function test_customer_counts_exclude_inactive_and_trashed_children(): void
    {
        $user = User::create(['name' => 'Test user', 'email' => 'test@example.test', 'password' => 'unused', 'email_verified_at' => now()]);
        $this->actingAs($user);
        $customer = Customer::factory()->create();
        $activeProject = Project::create(['name' => 'Active project', 'customer_id' => $customer->id]);
        $inactiveProject = Project::create(['name' => 'Inactive project', 'customer_id' => $customer->id, 'active' => false]);
        $trashedProject = Project::create(['name' => 'Trashed project', 'customer_id' => $customer->id]);
        $trashedProject->delete();

        foreach ([$activeProject, $inactiveProject, $trashedProject] as $project) {
            $epic = Epic::create(['name' => 'Active epic', 'project_id' => $project->id]);
            EpicComment::create(['epic_id' => $epic->id, 'user_id' => $user->id, 'body' => 'Comment']);
        }

        $inactiveEpic = Epic::create(['name' => 'Inactive epic', 'project_id' => $activeProject->id, 'active' => false]);
        $trashedEpic = Epic::create(['name' => 'Trashed epic', 'project_id' => $activeProject->id]);
        $trashedEpic->delete();

        foreach ([$inactiveEpic, $trashedEpic] as $epic) {
            EpicComment::create(['epic_id' => $epic->id, 'user_id' => $user->id, 'body' => 'Excluded comment']);
        }

        $record = (new CustomerListQuery)->active('')->items()[0];
        $this->assertSame($customer->id, $record->id);
        $this->assertSame(1, (int) $record->projects_count);
        $this->assertSame(1, (int) $record->epics_count);
        $this->assertSame(1, (int) $record->comments_count);

        $this->get(route('customers.index'))
            ->assertOk()
            ->assertSee(route('projects.index', ['search' => $customer->name]), false);
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

    public function test_a_customer_with_a_project_cannot_be_permanently_deleted_from_the_trash(): void
    {
        $customer = Customer::factory()->create();
        $customer->delete();
        $project = Project::create(['name' => 'Project added after trashing', 'customer_id' => $customer->id]);

        $this->actingAs(User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]));

        $this->delete(route('customers.trash.destroy', $customer->id))
            ->assertRedirect(route('customers.trash.index'))
            ->assertSessionHas('error', __('Cannot be permanently deleted while it has related records.'));

        $this->assertSoftDeleted($customer);
        $this->assertModelExists($project);
    }
}
