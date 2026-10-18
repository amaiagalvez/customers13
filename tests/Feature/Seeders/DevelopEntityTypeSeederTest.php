<?php

namespace entities13\Tests\Feature\Seeders;

use entities13\Tests\TestCase;
use entities13\Models\EntityType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Entities13\Database\Seeders\DevelopEntityTypeSeeder;

class DevelopEntityTypeSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_expected_active_archived_and_trashed_entity_types(): void
    {
        app(DevelopEntityTypeSeeder::class)->run(20);

        $this->assertDatabaseCount('ENT_entity_types', 25);
        $this->assertSame(15, EntityType::query()->where('active', true)->count());
        $this->assertSame(6, EntityType::query()->where('active', false)->count());
        $this->assertSame(4, EntityType::onlyTrashed()->count());
    }

    public function test_it_names_each_bulk_type_in_the_four_languages(): void
    {
        app(DevelopEntityTypeSeeder::class)->run(1);

        $this->assertDatabaseHas('ENT_entity_types', [
            'name_eu' => 'Perf Bezero Mota 00001',
            'name_es' => 'Perf Tipo Cliente 00001',
            'name_fr' => 'Perf Type Client 00001',
            'name_en' => 'Perf Entity Type 00001',
        ]);
    }

    public function test_it_creates_twenty_bulk_types_and_five_example_types_by_default(): void
    {
        $this->seed(DevelopEntityTypeSeeder::class);

        $this->assertDatabaseCount('ENT_entity_types', 25);
    }
}
