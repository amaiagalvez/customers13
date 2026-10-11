<?php

namespace Customers13\Tests\Feature\Seeders;

use Customers13\Tests\TestCase;
use Customers13\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Customers13\Database\Seeders\EdgeCaseCustomerSeeder;

class EdgeCaseCustomerSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_standalone_customers_and_parent_customers_for_project_fixtures(): void
    {
        $this->seed(EdgeCaseCustomerSeeder::class);

        $this->assertDatabaseCount('CUM_customers', 11);
        $this->assertSame(5, Customer::query()->where('name', 'like', 'Customer without projects %')->count());
        $this->assertSame(3, Customer::query()->where('name', 'like', 'Customer for project without epics %')->count());
        $this->assertSame(3, Customer::query()->where('name', 'like', 'Customer for epic without comments %')->count());
    }
}
