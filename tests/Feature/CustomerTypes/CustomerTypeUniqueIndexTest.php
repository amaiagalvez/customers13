<?php

namespace Customers13\Tests\Feature\CustomerTypes;

use Customers13\Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Customers13\Models\CustomerType;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * The uniqueness of a name is enforced per language by the database itself, not only by the form
 * request, so a write that skipped validation still cannot duplicate a name.
 */
class CustomerTypeUniqueIndexTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @var list<string>
     */
    private const NAME_COLUMNS = ['name_eu', 'name_es', 'name_fr', 'name_en'];

    public function test_every_language_column_has_its_own_unique_index(): void
    {
        $indexes = collect(DB::select(
            "select name from sqlite_master where type = 'index' and tbl_name = 'CUM_customer_types'",
        ))->pluck('name');

        foreach (self::NAME_COLUMNS as $column) {
            $this->assertContains('CUM_customer_types_active_'.$column.'_unique', $indexes);
        }
    }

    public function test_a_name_duplicated_in_any_language_hits_the_database_index(): void
    {
        // The first row carries the same wording in the four columns so the duplicate of any single
        // language has a stored value to collide with.
        CustomerType::factory()->create([
            'name_eu' => 'Mota bakarra',
            'name_es' => 'Mota bakarra',
            'name_fr' => 'Mota bakarra',
            'name_en' => 'Mota bakarra',
        ]);

        foreach (self::NAME_COLUMNS as $column) {
            $names = ['name_eu' => 'Beste mota bat'];
            $names[$column] = 'Mota bakarra';

            $this->assertDuplicateIsRefused($names);
        }
    }

    public function test_a_name_left_by_a_trashed_type_can_be_taken_again(): void
    {
        $trashed = CustomerType::factory()->trashed()->create([
            'name_eu' => 'Mota zaharkitua',
            'name_es' => null,
            'name_fr' => null,
            'name_en' => null,
        ]);

        // The same wording is accepted again, because the record that held it is in the trash.
        $replacement = CustomerType::factory()->create([
            'name_eu' => 'Mota zaharkitua',
            'name_es' => null,
            'name_fr' => null,
            'name_en' => null,
        ]);

        $this->assertSame('Mota zaharkitua', $replacement->name_eu);
        $this->assertSoftDeleted($trashed);
        $this->assertSame(1, CustomerType::query()->where('name_eu', 'Mota zaharkitua')->count());
        $this->assertSame(1, CustomerType::onlyTrashed()->where('name_eu', 'Mota zaharkitua')->count());
    }

    /**
     * @param  array<string, string>  $names
     */
    private function assertDuplicateIsRefused(array $names): void
    {
        $refused = false;

        try {
            CustomerType::factory()->create($names);
        } catch (QueryException) {
            $refused = true;
        }

        $this->assertTrue($refused, 'The database accepted a duplicate name.');
    }
}
