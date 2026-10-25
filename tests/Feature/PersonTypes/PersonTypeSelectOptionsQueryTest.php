<?php

namespace Entities13\Tests\Feature\PersonTypes;

use Entities13\Tests\TestCase;
use Entities13\Models\PersonType;
use Entities13\Contracts\EntityIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Entities13\Queries\PersonTypes\PersonTypeSelectOptionsQuery;

class PersonTypeSelectOptionsQueryTest extends TestCase
{
    use RefreshDatabase;

    protected PersonTypeSelectOptionsQuery $query;

    protected function setUp(): void
    {
        parent::setUp();
        $integration = $this->app->make(EntityIntegration::class);
        $this->query = new PersonTypeSelectOptionsQuery($integration);
    }

    public function test_search_returns_active_types_only(): void
    {
        PersonType::factory()->create(['name_eu' => 'Type A']);
        PersonType::factory()->archived()->create(['name_eu' => 'Type B']);
        PersonType::factory()->trashed()->create(['name_eu' => 'Type C']);

        $results = $this->query->search('');

        $this->assertCount(1, $results);
        $this->assertEquals('Type A', $results->first()['text']);
    }

    public function test_search_by_name_eu(): void
    {
        PersonType::factory()->create(['name_eu' => 'Type A']);
        PersonType::factory()->create(['name_eu' => 'Type B']);

        $results = $this->query->search('Type A');

        $this->assertCount(1, $results);
        $this->assertEquals('Type A', $results->first()['text']);
    }

    public function test_search_by_other_languages(): void
    {
        PersonType::factory()->create(['name_eu' => 'Mota A', 'name_es' => 'Tipo A']);
        PersonType::factory()->create(['name_eu' => 'Mota B', 'name_es' => 'Tipo B']);

        $results = $this->query->search('Tipo A');

        // The option is always named in euskera, whatever language the term matched.
        $this->assertCount(1, $results);
        $this->assertEquals('Mota A', $results->first()['text']);
    }

    public function test_search_limits_results(): void
    {
        PersonType::factory()->count(20)->create();

        $results = $this->query->search('');

        $this->assertCount(15, $results); // PER_PAGE = 15
    }

    public function test_results_have_id_and_text(): void
    {
        PersonType::factory()->create(['name_eu' => 'Test Type']);

        $results = $this->query->search('');

        $this->assertArrayHasKey('id', $results->first());
        $this->assertArrayHasKey('text', $results->first());
        $this->assertEquals('Test Type', $results->first()['text']);
    }
}
