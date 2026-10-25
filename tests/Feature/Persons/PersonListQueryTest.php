<?php

namespace Entities13\Tests\Feature\Persons;

use Entities13\Models\Entity;
use Entities13\Models\Person;
use Entities13\Tests\TestCase;
use Entities13\Models\PersonType;
use Entities13\Contracts\EntityIntegration;
use Entities13\Queries\Persons\PersonListQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PersonListQueryTest extends TestCase
{
    use RefreshDatabase;

    protected PersonListQuery $query;

    protected function setUp(): void
    {
        parent::setUp();
        $integration = $this->app->make(EntityIntegration::class);
        $this->query = new PersonListQuery($integration);
    }

    public function test_active_query_returns_only_active_persons(): void
    {
        Person::factory()->create();
        Person::factory()->archived()->create();
        Person::factory()->trashed()->create();

        $result = $this->query->active('');

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->active);
    }

    public function test_archived_query_returns_only_archived_persons(): void
    {
        Person::factory()->create();
        Person::factory()->archived()->create();
        Person::factory()->trashed()->create();

        $result = $this->query->archived('');

        $this->assertCount(1, $result);
        $this->assertFalse($result->first()->active);
        $this->assertNull($result->first()->deleted_at);
    }

    public function test_trashed_query_returns_only_trashed_persons(): void
    {
        Person::factory()->create();
        Person::factory()->archived()->create();
        Person::factory()->trashed()->create();

        $result = $this->query->trashed('');

        $this->assertCount(1, $result);
        $this->assertNotNull($result->first()->deleted_at);
    }

    public function test_search_by_name(): void
    {
        Person::factory()->create(['name' => 'Ander']);
        Person::factory()->create(['name' => 'Maria']);

        $result = $this->query->active('Ander');

        $this->assertCount(1, $result);
        $this->assertEquals('Ander', $result->first()->name);
    }

    public function test_search_by_last_name(): void
    {
        Person::factory()->create(['name' => 'Ander', 'last_name_1' => 'García']);
        Person::factory()->create(['name' => 'Maria', 'last_name_1' => 'Rodríguez']);

        $result = $this->query->active('García');

        $this->assertCount(1, $result);
        $this->assertEquals('García', $result->first()->last_name_1);
    }

    public function test_filter_by_entity_id(): void
    {
        $entity1 = Entity::factory()->create();
        $entity2 = Entity::factory()->create();

        Person::factory()->forEntity($entity1)->create();
        Person::factory()->forEntity($entity2)->create();

        $result = $this->query->active('', ['entity_id' => ['operator' => 'equals', 'value' => [$entity1->id]]]);

        $this->assertCount(1, $result);
        $this->assertEquals($entity1->id, $result->first()->entity_id);
    }

    public function test_filter_by_person_type_equals(): void
    {
        $type1 = PersonType::factory()->create();
        $type2 = PersonType::factory()->create();

        Person::factory()->withTypes([$type1])->create();
        Person::factory()->withTypes([$type2])->create();

        $result = $this->query->active('', ['person_type_ids' => ['operator' => 'equals', 'value' => [$type1->id]]]);

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->personTypes->contains('id', $type1->id));
    }

    public function test_filter_by_person_type_not_equals(): void
    {
        $type1 = PersonType::factory()->create();
        $type2 = PersonType::factory()->create();

        Person::factory()->withTypes([$type1])->create();
        Person::factory()->withTypes([$type2])->create();

        $result = $this->query->active('', ['person_type_ids' => ['operator' => 'not_equals', 'value' => [$type1->id]]]);

        $this->assertCount(1, $result);
        $this->assertFalse($result->first()->personTypes->contains('id', $type1->id));
    }

    public function test_filter_by_lang(): void
    {
        Person::factory()->create(['lang' => 'eu']);
        Person::factory()->create(['lang' => 'es']);

        $result = $this->query->active('', ['lang' => ['operator' => 'equals', 'value' => 'eu']]);

        $this->assertCount(1, $result);
        $this->assertEquals('eu', $result->first()->lang);
    }

    public function test_filtered_query_includes_relations(): void
    {
        $entity = Entity::factory()->create();
        $type = PersonType::factory()->create();
        Person::factory()->forEntity($entity)->withTypes([$type])->create();

        $query = $this->query->filteredQuery('');

        $person = $query->first();
        $this->assertNotNull($person);
        $this->assertTrue($person->relationLoaded('entity'));
        $this->assertTrue($person->relationLoaded('personTypes'));
    }
}
