<?php

namespace Entities13\Tests\Feature\Persons;

use Entities13\Models\Entity;
use Entities13\Models\Person;
use Entities13\Tests\TestCase;
use Entities13\Models\PersonType;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PersonExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->createAdminUser());
    }

    public function test_can_export_persons(): void
    {
        Person::factory()->count(3)->create();

        $response = $this->get(route('persons.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_export_respects_search_filter(): void
    {
        Person::factory()->create(['name' => 'Ander', 'last_name_1' => 'García']);
        Person::factory()->create(['name' => 'Maria', 'last_name_1' => 'Rodríguez']);

        $response = $this->get(route('persons.export', ['search' => 'Ander']));

        $response->assertOk();
    }

    public function test_export_respects_entity_filter(): void
    {
        $entity1 = Entity::factory()->create(['name' => 'Entity A']);
        $entity2 = Entity::factory()->create(['name' => 'Entity B']);

        Person::factory()->forEntity($entity1)->create(['name' => 'Ander']);
        Person::factory()->forEntity($entity2)->create(['name' => 'Maria']);

        $response = $this->get(route('persons.export', ['filters' => ['entity_id' => ['operator' => 'equals', 'value' => [$entity1->id]]]]));

        $response->assertOk();
    }

    public function test_export_respects_person_type_filter(): void
    {
        $type1 = PersonType::factory()->create(['name_eu' => 'Type A']);
        $type2 = PersonType::factory()->create(['name_eu' => 'Type B']);

        Person::factory()->withTypes([$type1])->create(['name' => 'Ander']);
        Person::factory()->withTypes([$type2])->create(['name' => 'Maria']);

        $response = $this->get(route('persons.export', ['filters' => ['person_type_ids' => ['operator' => 'equals', 'value' => [$type1->id]]]]));

        $response->assertOk();
    }

    public function test_export_includes_trashed_when_scope_trashed(): void
    {
        Person::factory()->create(['name' => 'Active']);
        Person::factory()->trashed()->create(['name' => 'Trashed']);

        $response = $this->get(route('persons.export', ['scope' => 'trashed']));

        $response->assertOk();
    }
}
