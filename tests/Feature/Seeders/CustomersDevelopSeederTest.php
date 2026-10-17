<?php

namespace Customers13\Tests\Feature\Seeders;

use Customers13\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Customers13\Database\Seeders\CustomersDevelopSeeder;

class CustomersDevelopSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_customers_and_customer_types_with_their_assignments(): void
    {
        $this->seed(CustomersDevelopSeeder::class);

        $this->assertDatabaseCount('CUM_customers', 101);
        $this->assertDatabaseHas('CUM_customers', ['name' => 'Perf Customer 00100']);
        $this->assertDatabaseHas('CUM_customers', ['name' => 'Customer holding customer types 0']);
        $this->assertDatabaseCount('CUM_customer_types', 25);
        $this->assertDatabaseCount('CUM_customer_customer_type', 2);
    }
}
