<?php

namespace Customers13\Database\Seeders;

use Illuminate\Database\Seeder;
use Customers13\Models\Customer;

class EdgeCaseCustomerSeeder extends Seeder
{
    public function run(): void
    {
        Customer::factory()->count(5)->sequence(
            fn ($sequence): array => ['name' => "Customer without projects {$sequence->index}"],
        )->create();

        Customer::factory()->count(3)->sequence(
            fn ($sequence): array => ['name' => "Customer for project without epics {$sequence->index}"],
        )->create();

        Customer::factory()->count(3)->sequence(
            fn ($sequence): array => ['name' => "Customer for epic without comments {$sequence->index}"],
        )->create();
    }
}
