<?php

namespace Entities13\Tests;

use Illuminate\Database\Seeder;
use Basics13\Database\Seeders\BasicsAccessSeeder;
use Entities13\Database\Seeders\EntitiesAccessSeeder;

/**
 * What `db:seed` gives the application for this package: basics13 first, then entities13.
 */
class AccessSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([BasicsAccessSeeder::class, EntitiesAccessSeeder::class]);
    }
}
