<?php

namespace Entities13\Tests\Feature;

use Entities13\Models\Entity;
use Entities13\Models\Person;
use Entities13\Tests\TestCase;
use Entities13\Models\PersonType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Entities13\Database\Seeders\DevelopPersonSeeder;
use Entities13\Database\Seeders\DevelopPersonTypeSeeder;

class SeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_develop_person_type_seeder_creates_expected_data(): void
    {
        app(DevelopPersonTypeSeeder::class)->run(10);

        $this->assertDatabaseCount('ENT_person_types', 15); // 5 fixed + 10 bulk
        $this->assertDatabaseHas('ENT_person_types', ['name_eu' => 'Pertsona mota euskaraz bakarrik']);
        $this->assertDatabaseHas('ENT_person_types', ['name_eu' => 'Pertsona estrategikoa']);
    }

    public function test_develop_person_seeder_creates_expected_data(): void
    {
        Entity::factory()->create(['name' => 'Test Entity']);
        PersonType::factory()->create(['name_eu' => 'Test Type']);

        app(DevelopPersonSeeder::class)->run(5);

        $this->assertDatabaseCount('ENT_persons', 9); // 4 fixed + 5 bulk
        $this->assertDatabaseHas('ENT_persons', ['name' => 'Ander']);
        $this->assertDatabaseHas('ENT_persons', ['name' => 'Maria']);
        $this->assertDatabaseHas('ENT_persons', ['name' => 'Jon']);
        $this->assertDatabaseHas('ENT_persons', ['name' => 'Leire']);
    }

    public function test_develop_person_seeder_requires_existing_entities(): void
    {
        app(DevelopPersonSeeder::class)->run(5);

        // Should not create persons without entities
        $this->assertDatabaseCount('ENT_persons', 0);
    }

    public function test_develop_person_seeder_requires_existing_person_types(): void
    {
        Entity::factory()->create(['name' => 'Test Entity']);

        app(DevelopPersonSeeder::class)->run(5);

        // Should not create persons without person types
        $this->assertDatabaseCount('ENT_persons', 0);
    }
}
