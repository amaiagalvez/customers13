<?php

namespace entities13\Tests\Feature\Seeders;

use entities13\Tests\TestCase;
use entities13\Models\Entity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use entities13\Database\Seeders\DevelopEntitySeeder;

class DevelopEntitySeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_expected_active_archived_and_trashed_entities(): void
    {
        app(DevelopEntitySeeder::class)->run(20);

        $this->assertDatabaseCount('CUM_entities', 20);
        $this->assertSame(12, Entity::query()->where('active', true)->count());
        $this->assertSame(5, Entity::query()->where('active', false)->count());
        $this->assertSame(3, Entity::onlyTrashed()->count());
        $this->assertDatabaseHas('CUM_entities', ['name' => 'Perf Entity 00020']);
    }
}
