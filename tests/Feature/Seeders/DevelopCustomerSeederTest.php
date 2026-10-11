<?php

namespace Customers13\Tests\Feature\Seeders;

use Customers13\Tests\TestCase;
use Customers13\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Customers13\Database\Seeders\DevelopCustomerSeeder;

class DevelopCustomerSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_expected_active_archived_and_trashed_customers(): void
    {
        app(DevelopCustomerSeeder::class)->run(20);

        $this->assertDatabaseCount('CUM_customers', 20);
        $this->assertSame(12, Customer::query()->where('active', true)->count());
        $this->assertSame(5, Customer::query()->where('active', false)->count());
        $this->assertSame(3, Customer::onlyTrashed()->count());
        $this->assertDatabaseHas('CUM_customers', ['name' => 'Perf Customer 00020']);
    }
}
