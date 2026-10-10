<?php

namespace Customers13\Tests\Feature\Customers;

use App\Models\Epic;
use App\Models\User;
use App\Models\Project;
use App\Models\EpicComment;
use Customers13\Tests\TestCase;
use Customers13\Models\Customer;
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
}
