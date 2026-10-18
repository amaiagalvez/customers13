<?php

namespace Customers13\Tests\Feature\CustomerTypes;

use App\Models\User;
use Customers13\Tests\TestCase;
use Customers13\Models\Customer;
use Illuminate\Support\Facades\DB;
use Customers13\Models\CustomerType;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

class CustomerTypeTrashTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('customer-types.trash.index'))->assertRedirect(route('login'));
    }

    public function test_trashed_types_are_listed_in_the_trash_and_not_in_the_active_list(): void
    {
        $this->actingAs($this->user());
        CustomerType::factory()->create(['name_eu' => 'Mota aktiboa']);
        CustomerType::factory()->trashed()->create(['name_eu' => 'Mota ezabatua']);

        $this->get(route('customer-types.index'))
            ->assertOk()
            ->assertSee('Mota aktiboa')
            ->assertDontSee('Mota ezabatua');

        $this->get(route('customer-types.trash.index'))
            ->assertOk()
            ->assertSee('Mota ezabatua')
            ->assertDontSee('Mota aktiboa');
    }

    public function test_a_trashed_type_can_be_restored(): void
    {
        $this->actingAs($this->user());
        $customerType = CustomerType::factory()->trashed()->create();

        $this->patch(route('customer-types.trash.restore', $customerType->id))
            ->assertRedirect(route('customer-types.trash.index'))
            ->assertSessionHas('status', __('basics13::messages.restored'));

        $this->assertNotSoftDeleted($customerType);
    }

    public function test_restoring_a_trashed_archived_type_preserves_its_archived_state(): void
    {
        $this->actingAs($this->user());
        $customerType = CustomerType::factory()->archived()->trashed()->create();

        $this->patch(route('customer-types.trash.restore', $customerType->id))
            ->assertRedirect(route('customer-types.trash.index'));

        $this->assertNotSoftDeleted($customerType);
        $this->assertFalse($customerType->refresh()->active);
    }

    public function test_a_trashed_type_cannot_be_restored_while_a_live_type_uses_any_of_its_names(): void
    {
        $this->actingAs($this->user());
        $customerType = CustomerType::factory()->trashed()->create([
            'name_eu' => 'Mota zaharkitua',
            'name_es' => 'Tipo en la papelera',
        ]);

        // The euskera name is free, but the Spanish one is taken: a type is named in four languages
        // and restoring it puts all four back at once.
        CustomerType::factory()->create([
            'name_eu' => 'Beste mota bat',
            'name_es' => 'Tipo en la papelera',
        ]);

        $this->patch(route('customer-types.trash.restore', $customerType->id))
            ->assertRedirect(route('customer-types.trash.index'))
            ->assertSessionHas('error', __('basics13::messages.cannot_restore_name_taken'));

        $this->assertSoftDeleted($customerType);
    }

    public function test_a_restore_that_resolves_the_name_conflict_reports_it(): void
    {
        $this->actingAs($this->user());
        $customerType = CustomerType::factory()->trashed()->create(['name_eu' => 'Mota zaharkitua']);

        $this->patch(route('customer-types.trash.restore', $customerType->id), [
            'resolve_name_conflict' => true,
        ])
            ->assertRedirect(route('customer-types.trash.index'))
            ->assertSessionHas('status', __('basics13::messages.restored_no_new_record'));

        $this->assertNotSoftDeleted($customerType);
    }

    public function test_a_trashed_type_no_customer_points_at_can_be_permanently_deleted(): void
    {
        $this->actingAs($this->user());
        $customerType = CustomerType::factory()->trashed()->create();

        $this->delete(route('customer-types.trash.destroy', $customerType->id))
            ->assertRedirect(route('customer-types.trash.index'))
            ->assertSessionHas('status', __('basics13::messages.permanently_deleted'));

        $this->assertDatabaseMissing('CUM_customer_types', ['id' => $customerType->id]);
    }

    public function test_a_force_delete_is_refused_by_the_pivot_foreign_key_while_a_customer_points_at_the_type(): void
    {
        $this->actingAs($this->user());
        $customerType = CustomerType::factory()->trashed()->create();
        // A trashed type a customer still points at is not reachable through the application (the
        // soft delete is refused first), so the pivot row is written directly to exercise the
        // foreign key that backstops the guard.
        Customer::factory()->create()->customerTypes()->attach($customerType);

        $this->delete(route('customer-types.trash.destroy', $customerType->id))
            ->assertRedirect(route('customer-types.trash.index'))
            ->assertSessionHas('error', __('basics13::messages.cannot_force_delete_related'));

        $this->assertDatabaseHas('CUM_customer_types', ['id' => $customerType->id]);
    }

    public function test_the_trash_list_blocks_the_permanent_delete_while_a_customer_points_at_the_type(): void
    {
        $this->actingAs($this->user());
        $customerType = CustomerType::factory()->trashed()->create();
        Customer::factory()->create()->customerTypes()->attach($customerType);

        $this->get(route('customer-types.trash.index'))
            ->assertOk()
            ->assertSee('data-test="customer-type-force-delete-blocked-'.$customerType->id.'"', false)
            ->assertDontSee('data-test="customer-type-force-delete-'.$customerType->id.'"', false)
            ->assertSee(__('basics13::messages.cannot_force_delete_related'));
    }

    public function test_restore_converts_a_concurrent_duplicate_name_to_a_conflict(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('The concurrent restore guard requires MySQL row locks.');
        }

        $this->actingAs($this->user());
        $name = 'Concurrent Restore CustomerType';
        $deleted = CustomerType::factory()->trashed()->create([
            'name_eu' => $name,
            'name_es' => $name,
            'name_fr' => $name,
            'name_en' => $name,
        ]);

        $competitorCreated = false;

        DB::listen(static function (QueryExecuted $query) use ($deleted, $name, &$competitorCreated): void {
            $sql = strtolower(ltrim($query->sql));
            $bindings = $query->bindings;

            if (
                $competitorCreated
                || ! str_starts_with($sql, 'select')
                || ! str_contains($sql, 'cum_customer_types')
                || ! in_array($deleted->name_eu, $bindings, true)
            ) {
                return;
            }

            $competitorCreated = true;
            CustomerType::factory()->create([
                'name_eu' => $name,
                'name_es' => $name,
                'name_fr' => $name,
                'name_en' => $name,
            ]);
        });

        $this->patch(route('customer-types.trash.restore', $deleted->id))
            ->assertRedirect(route('customer-types.trash.index'))
            ->assertSessionHas('error', __('basics13::messages.cannot_restore_name_taken'));

        $this->assertSoftDeleted($deleted);
        $this->assertDatabaseHas('CUM_customer_types', ['name_eu' => $name, 'deleted_at' => null]);
    }

    private function user(): User
    {
        return User::create([
            'name' => 'Test user',
            'email' => 'test@example.test',
            'password' => 'unused',
            'email_verified_at' => now(),
        ]);
    }
}
