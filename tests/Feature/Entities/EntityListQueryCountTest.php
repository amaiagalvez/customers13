<?php

namespace Entities13\Tests\Feature\Entities;

use Entities13\Models\Entity;
use Entities13\Models\Person;
use Entities13\Tests\TestCase;
use Entities13\Models\EntityType;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EntityListQueryCountTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_entity_list_makes_the_same_queries_whatever_the_number_of_rows(): void
    {
        $this->actingAs($this->createAdminUser());
        $this->seedEntities(3);
        $this->get(route('entities.index'))->assertOk();

        $few = $this->queriesFor(route('entities.index'));
        $this->seedEntities(12);
        $many = $this->queriesFor(route('entities.index'));

        $this->assertGreaterThan(0, $few);
        $this->assertSame($few, $many, 'The entity list queries grow with its rows.');
    }

    private function seedEntities(int $count): void
    {
        for ($index = 0; $index < $count; $index++) {
            $entity = Entity::factory()->create();
            $entity->entityTypes()->attach(EntityType::factory()->create());
            Person::factory()->forEntity($entity)->create();
        }
    }

    private function queriesFor(string $url): int
    {
        $queries = 0;
        DB::listen(static function () use (&$queries): void {
            $queries++;
        });

        $this->get($url)->assertOk();

        return $queries;
    }
}
