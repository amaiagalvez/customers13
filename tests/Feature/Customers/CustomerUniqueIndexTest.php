<?php

namespace Customers13\Tests\Feature\Customers;

use Customers13\Tests\TestCase;
use Customers13\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * The uniqueness of a customer name is enforced by the database itself via a partial unique index
 * on the generated `active_name` column, so a write that skipped validation still cannot
 * duplicate an active name.
 */
class CustomerUniqueIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_table_has_the_partial_unique_index_on_active_name(): void
    {
        $indexes = collect(DB::select(
            "select name from sqlite_master where type = 'index' and tbl_name = 'CUM_customers'",
        ))->pluck('name');

        $this->assertContains('CUM_customers_active_name_unique', $indexes);
    }

    public function test_a_duplicate_active_name_hits_the_database_index(): void
    {
        Customer::factory()->create(['name' => 'Ane Bezeroa']);

        $refused = false;

        try {
            Customer::factory()->create(['name' => 'Ane Bezeroa']);
        } catch (QueryException) {
            $refused = true;
        }

        $this->assertTrue($refused, 'The database accepted a duplicate active name.');
    }

    public function test_a_name_left_by_a_trashed_customer_can_be_taken_again(): void
    {
        $trashed = Customer::factory()->trashed()->create(['name' => 'Mota zaharkitua']);

        // The same name is accepted again, because the record that held it is in the trash.
        $replacement = Customer::factory()->create(['name' => 'Mota zaharkitua']);

        $this->assertSame('Mota zaharkitua', $replacement->name);
        $this->assertSoftDeleted($trashed);
        $this->assertSame(1, Customer::query()->where('name', 'Mota zaharkitua')->count());
        $this->assertSame(1, Customer::onlyTrashed()->where('name', 'Mota zaharkitua')->count());
    }
}
