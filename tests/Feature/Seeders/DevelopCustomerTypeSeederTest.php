<?php

namespace Customers13\Tests\Feature\Seeders;

use Customers13\Tests\TestCase;
use Customers13\Models\CustomerType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Customers13\Database\Seeders\DevelopCustomerTypeSeeder;

class DevelopCustomerTypeSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_expected_active_archived_and_trashed_customer_types(): void
    {
        app(DevelopCustomerTypeSeeder::class)->run(20);

        $this->assertDatabaseCount('CUM_customer_types', 25);
        $this->assertSame(15, CustomerType::query()->where('active', true)->count());
        $this->assertSame(6, CustomerType::query()->where('active', false)->count());
        $this->assertSame(4, CustomerType::onlyTrashed()->count());
    }

    public function test_it_names_each_bulk_type_in_the_four_languages(): void
    {
        app(DevelopCustomerTypeSeeder::class)->run(1);

        $this->assertDatabaseHas('CUM_customer_types', [
            'name_eu' => 'Perf Bezero Mota 00001',
            'name_es' => 'Perf Tipo Cliente 00001',
            'name_fr' => 'Perf Type Client 00001',
            'name_en' => 'Perf Customer Type 00001',
        ]);
    }

    public function test_it_creates_twenty_bulk_types_and_five_example_types_by_default(): void
    {
        $this->seed(DevelopCustomerTypeSeeder::class);

        $this->assertDatabaseCount('CUM_customer_types', 25);
    }
}
