<?php

namespace entities13\Tests\Feature\Seeders;

use entities13\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use entities13\Database\Seeders\EntitiesDevelopSeeder;

class EntitiesDevelopSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_seeds_entities_and_entity_types_with_their_assignments(): void
    {
        $this->seed(EntitiesDevelopSeeder::class);

        $this->assertDatabaseCount('CUM_entities', 101);
        $this->assertDatabaseHas('CUM_entities', ['name' => 'Perf Entity 00100']);
        $this->assertDatabaseHas('CUM_entities', ['name' => 'Entity holding entity types 0']);
        $this->assertDatabaseCount('CUM_entity_types', 25);
        $this->assertDatabaseCount('CUM_entity_entity_type', 2);
    }
}
