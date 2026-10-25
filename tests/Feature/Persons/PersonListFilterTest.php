<?php

namespace Entities13\Tests\Feature\Persons;

use Entities13\Models\Entity;
use Entities13\Models\Person;
use Entities13\Tests\TestCase;
use Entities13\Models\PersonType;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PersonListFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->createAdminUser());
    }

    public function test_can_filter_persons_by_name(): void
    {
        Person::factory()->create(['name' => 'Ander', 'last_name_1' => 'García']);
        Person::factory()->create(['name' => 'Maria', 'last_name_1' => 'Rodríguez']);

        $response = $this->get(route('persons.index', ['search' => 'Ander']));

        $response->assertOk();
        $response->assertSee('Ander');
        $response->assertDontSee('Maria');
    }

    public function test_can_filter_persons_by_last_name(): void
    {
        Person::factory()->create(['name' => 'Ander', 'last_name_1' => 'García']);
        Person::factory()->create(['name' => 'Maria', 'last_name_1' => 'Rodríguez']);

        $response = $this->get(route('persons.index', ['search' => 'García']));

        $response->assertOk();
        $response->assertSee('Ander');
        $response->assertDontSee('Maria');
    }

    public function test_can_filter_persons_by_document_number(): void
    {
        Person::factory()->create(['name' => 'Ander', 'document_number' => '12345678A']);
        Person::factory()->create(['name' => 'Maria', 'document_number' => '87654321B']);

        $response = $this->get(route('persons.index', ['search' => '12345678A']));

        $response->assertOk();
        $response->assertSee('Ander');
        $response->assertDontSee('Maria');
    }

    public function test_can_filter_persons_by_entity(): void
    {
        $entity1 = Entity::factory()->create(['name' => 'Entity A']);
        $entity2 = Entity::factory()->create(['name' => 'Entity B']);

        Person::factory()->forEntity($entity1)->create(['name' => 'Ander']);
        Person::factory()->forEntity($entity2)->create(['name' => 'Maria']);

        $response = $this->get(route('persons.index', ['filters' => ['entity_id' => ['operator' => 'equals', 'value' => [$entity1->id]]]]));

        $response->assertOk();
        $response->assertSee('Ander');
        $response->assertDontSee('Maria');
    }

    public function test_can_filter_persons_by_person_type_equals(): void
    {
        $type1 = PersonType::factory()->create(['name_eu' => 'Type A']);
        $type2 = PersonType::factory()->create(['name_eu' => 'Type B']);

        Person::factory()->withTypes([$type1])->create(['name' => 'Ander']);
        Person::factory()->withTypes([$type2])->create(['name' => 'Maria']);

        $response = $this->get(route('persons.index', ['filters' => ['person_type_ids' => ['operator' => 'equals', 'value' => [$type1->id]]]]));

        $response->assertOk();
        $response->assertSee('Ander');
        $response->assertDontSee('Maria');
    }

    public function test_a_person_type_filter_without_a_value_filters_nothing(): void
    {
        $type = PersonType::factory()->create(['name_eu' => 'Type A']);

        Person::factory()->withTypes([$type])->create(['name' => 'Ander']);
        Person::factory()->create(['name' => 'Maria']);

        $response = $this->get(route('persons.index', ['filters' => ['person_type_ids' => ['operator' => 'equals', 'value' => ['']]]]));

        $response->assertOk();
        $response->assertSee('Ander');
        $response->assertSee('Maria');
    }

    public function test_can_filter_persons_by_person_type_not_equals(): void
    {
        $type1 = PersonType::factory()->create(['name_eu' => 'Type A']);
        $type2 = PersonType::factory()->create(['name_eu' => 'Type B']);

        Person::factory()->withTypes([$type1])->create(['name' => 'Ander']);
        Person::factory()->withTypes([$type2])->create(['name' => 'Maria']);

        $response = $this->get(route('persons.index', ['filters' => ['person_type_ids' => ['operator' => 'not_equals', 'value' => [$type1->id]]]]));

        $response->assertOk();
        $response->assertSee('Maria');
        $response->assertDontSee('Ander');
    }

    public function test_can_filter_persons_by_lang(): void
    {
        Person::factory()->create(['name' => 'Ander', 'lang' => 'eu']);
        Person::factory()->create(['name' => 'Maria', 'lang' => 'es']);

        $response = $this->get(route('persons.index', ['filters' => ['lang' => ['operator' => 'equals', 'value' => 'eu']]]));

        $response->assertOk();
        $response->assertSee('Ander');
        $response->assertDontSee('Maria');
    }

    public function test_can_filter_persons_by_active_status_in_trash(): void
    {
        Person::factory()->create(['name' => 'Active Person']);
        Person::factory()->archived()->create(['name' => 'Archived Person']);
        Person::factory()->trashed()->create(['name' => 'Trashed Person']);

        $response = $this->get(route('persons.trash.index', ['filters' => ['active' => ['operator' => 'is_true', 'value' => true]]]));

        $response->assertOk();
    }
}
