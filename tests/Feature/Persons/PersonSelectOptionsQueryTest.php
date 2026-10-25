<?php

namespace Entities13\Tests\Feature\Persons;

use Entities13\Models\Person;
use Entities13\Tests\TestCase;
use Entities13\Contracts\EntityIntegration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Entities13\Queries\Persons\PersonSelectOptionsQuery;

class PersonSelectOptionsQueryTest extends TestCase
{
    use RefreshDatabase;

    protected PersonSelectOptionsQuery $query;

    protected function setUp(): void
    {
        parent::setUp();
        $integration = $this->app->make(EntityIntegration::class);
        $this->query = new PersonSelectOptionsQuery($integration);
    }

    public function test_search_returns_active_persons_only(): void
    {
        Person::factory()->create(['name' => 'Ander', 'last_name_1' => null, 'last_name_2' => null]);
        Person::factory()->archived()->create(['name' => 'Maria', 'last_name_1' => null, 'last_name_2' => null]);
        Person::factory()->trashed()->create(['name' => 'Jon', 'last_name_1' => null, 'last_name_2' => null]);

        $results = $this->query->search('');

        $this->assertCount(1, $results);
        $this->assertEquals('Ander', $results->first()['text']);
    }

    public function test_search_by_name(): void
    {
        Person::factory()->create(['name' => 'Ander', 'last_name_1' => null, 'last_name_2' => null]);
        Person::factory()->create(['name' => 'Maria', 'last_name_1' => null, 'last_name_2' => null]);

        $results = $this->query->search('Ander');

        $this->assertCount(1, $results);
        $this->assertEquals('Ander', $results->first()['text']);
    }

    public function test_search_by_last_name(): void
    {
        Person::factory()->create(['name' => 'Ander', 'last_name_1' => 'García']);
        Person::factory()->create(['name' => 'Maria', 'last_name_1' => 'Rodríguez']);

        $results = $this->query->search('García');

        $this->assertCount(1, $results);
        $this->assertStringContainsString('Ander', $results->first()['text']);
    }

    public function test_search_limits_results(): void
    {
        Person::factory()->count(20)->create();

        $results = $this->query->search('');

        $this->assertCount(15, $results); // PER_PAGE = 15
    }

    public function test_results_have_id_and_text(): void
    {
        Person::factory()->create(['name' => 'Ander', 'last_name_1' => 'García', 'last_name_2' => null]);

        $results = $this->query->search('');

        $this->assertArrayHasKey('id', $results->first());
        $this->assertArrayHasKey('text', $results->first());
        $this->assertEquals('Ander García', $results->first()['text']);
    }
}
