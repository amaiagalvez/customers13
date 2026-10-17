<?php

namespace Customers13\Tests\Feature\Seeders;

use Customers13\Tests\TestCase;
use Customers13\Models\CustomerType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Customers13\Database\Seeders\DevelopCustomerTypeSeeder;

class DevelopCustomerTypeEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_example_types_and_the_customer_that_holds_them(): void
    {
        app(DevelopCustomerTypeSeeder::class)->run(0);

        $this->assertDatabaseCount('CUM_customer_types', 5);
        $this->assertSame(1, CustomerType::onlyTrashed()->count());
        $this->assertSame(1, CustomerType::query()->where('active', false)->count());
        $this->assertDatabaseCount('CUM_customers', 1);
        $this->assertDatabaseHas('CUM_customers', ['name' => 'Customer holding customer types 0']);
        $this->assertDatabaseCount('CUM_customer_customer_type', 2);
    }

    public function test_it_seeds_a_type_that_only_has_an_euskera_name(): void
    {
        app(DevelopCustomerTypeSeeder::class)->run(0);

        $this->assertSame(1, CustomerType::query()
            ->where('name_eu', 'Bezero mota euskaraz bakarrik')
            ->whereNull('name_es')
            ->whereNull('name_fr')
            ->whereNull('name_en')
            ->count());
    }

    public function test_the_seeded_customer_keeps_the_types_it_holds_from_being_deleted(): void
    {
        app(DevelopCustomerTypeSeeder::class)->run(0);

        $held = CustomerType::query()->where('name_eu', 'Bezero mota erabilia')->firstOrFail();
        $archivedHeld = CustomerType::query()->where('name_eu', 'Bezero mota zaharkitua erabilia')->firstOrFail();

        $this->assertFalse($held->delete());
        $this->assertFalse($archivedHeld->delete());
        $this->assertDatabaseHas('CUM_customer_types', ['id' => $held->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('CUM_customer_types', ['id' => $archivedHeld->id, 'deleted_at' => null]);
    }
}
