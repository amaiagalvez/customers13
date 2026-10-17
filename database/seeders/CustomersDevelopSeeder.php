<?php

namespace Customers13\Database\Seeders;

use Illuminate\Database\Seeder;

class CustomersDevelopSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DevelopCustomerSeeder::class);
        $this->call(DevelopCustomerTypeSeeder::class);
    }
}
