<?php

namespace Entities13\Database\Seeders;

use Illuminate\Database\Seeder;

class EntitiesDevelopSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DevelopEntitySeeder::class);
        $this->call(DevelopEntityTypeSeeder::class);
        $this->call(DevelopPersonTypeSeeder::class);
        $this->call(DevelopPersonSeeder::class);
    }
}
