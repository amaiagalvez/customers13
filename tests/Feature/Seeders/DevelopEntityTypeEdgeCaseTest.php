<?php

namespace Entities13\Tests\Feature\Seeders;

use Entities13\Tests\TestCase;
use Entities13\Models\EntityType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Entities13\Database\Seeders\DevelopEntityTypeSeeder;

class DevelopEntityTypeEdgeCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_the_example_types_and_the_entity_that_holds_them(): void
    {
        app(DevelopEntityTypeSeeder::class)->run(0);

        $this->assertDatabaseCount('ENT_entity_types', 5);
        $this->assertSame(1, EntityType::onlyTrashed()->count());
        $this->assertSame(1, EntityType::query()->where('active', false)->count());
        $this->assertDatabaseCount('ENT_entities', 1);
        $this->assertDatabaseHas('ENT_entities', ['name' => 'Entity holding entity types 0']);
        $this->assertDatabaseCount('ENT_entity_entity_type', 2);
    }

    public function test_it_seeds_a_type_that_only_has_an_euskera_name(): void
    {
        app(DevelopEntityTypeSeeder::class)->run(0);

        $this->assertSame(1, EntityType::query()
            ->where('name_eu', 'Bezero mota euskaraz bakarrik')
            ->whereNull('name_es')
            ->whereNull('name_fr')
            ->whereNull('name_en')
            ->count());
    }

    public function test_the_seeded_entity_keeps_the_types_it_holds_from_being_deleted(): void
    {
        app(DevelopEntityTypeSeeder::class)->run(0);

        $held = EntityType::query()->where('name_eu', 'Bezero mota erabilia')->firstOrFail();
        $archivedHeld = EntityType::query()->where('name_eu', 'Bezero mota zaharkitua erabilia')->firstOrFail();

        $this->assertFalse($held->delete());
        $this->assertFalse($archivedHeld->delete());
        $this->assertDatabaseHas('ENT_entity_types', ['id' => $held->id, 'deleted_at' => null]);
        $this->assertDatabaseHas('ENT_entity_types', ['id' => $archivedHeld->id, 'deleted_at' => null]);
    }
}
