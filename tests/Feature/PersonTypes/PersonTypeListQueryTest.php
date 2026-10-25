<?php

namespace Entities13\Tests\Feature\PersonTypes;

use Entities13\Models\Person;
use Entities13\Tests\TestCase;
use Entities13\Models\PersonType;
use Entities13\Contracts\EntityIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Entities13\Queries\PersonTypes\PersonTypeListQuery;

class PersonTypeListQueryTest extends TestCase
{
    use RefreshDatabase;

    protected PersonTypeListQuery $query;

    protected function setUp(): void
    {
        parent::setUp();
        $integration = $this->app->make(EntityIntegration::class);
        $this->query = new PersonTypeListQuery($integration);
    }

    public function test_active_query_returns_only_active_types(): void
    {
        PersonType::factory()->create();
        PersonType::factory()->archived()->create();
        PersonType::factory()->trashed()->create();

        $result = $this->query->active('');

        $this->assertCount(1, $result);
        $this->assertTrue($result->first()->active);
    }

    public function test_archived_query_returns_only_archived_types(): void
    {
        PersonType::factory()->create();
        PersonType::factory()->archived()->create();
        PersonType::factory()->trashed()->create();

        $result = $this->query->archived('');

        $this->assertCount(1, $result);
        $this->assertFalse($result->first()->active);
        $this->assertNull($result->first()->deleted_at);
    }

    public function test_trashed_query_returns_only_trashed_types(): void
    {
        PersonType::factory()->create();
        PersonType::factory()->archived()->create();
        PersonType::factory()->trashed()->create();

        $result = $this->query->trashed('');

        $this->assertCount(1, $result);
        $this->assertNotNull($result->first()->deleted_at);
    }

    public function test_search_by_name_eu(): void
    {
        PersonType::factory()->create(['name_eu' => 'Type A']);
        PersonType::factory()->create(['name_eu' => 'Type B']);

        $result = $this->query->active('Type A');

        $this->assertCount(1, $result);
        $this->assertEquals('Type A', $result->first()->name_eu);
    }

    public function test_search_by_other_languages(): void
    {
        PersonType::factory()->create(['name_es' => 'Tipo A']);
        PersonType::factory()->create(['name_es' => 'Tipo B']);

        $result = $this->query->active('Tipo A');

        $this->assertCount(1, $result);
        $this->assertEquals('Tipo A', $result->first()->name_es);
    }

    public function test_persons_count_includes_trashed(): void
    {
        $type = PersonType::factory()->create();
        Person::factory()->withTypes([$type])->create();
        Person::factory()->withTypes([$type])->create()->delete();

        $result = $this->query->active('');

        $this->assertEquals(2, $result->first()->getAttribute('persons_count'));
    }

    public function test_find_trashed_by_name(): void
    {
        $trashed = PersonType::factory()->trashed()->create(['name_eu' => 'Trashed Type']);

        $found = $this->query->findTrashedByName('Trashed Type');

        $this->assertNotNull($found);
        $this->assertEquals($trashed->id, $found->id);
    }
}
