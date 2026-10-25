<?php

namespace Entities13\Tests\Feature\Persons;

use Entities13\Models\Person;
use Entities13\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PersonArchivedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->createAdminUser());
    }

    public function test_can_view_archived_persons(): void
    {
        $archived = Person::factory()->archived()->create();

        $response = $this->get(route('persons.archived.index'));

        $response->assertOk();
        $response->assertSee($archived->getFullName());
    }

    public function test_can_archive_person(): void
    {
        $person = Person::factory()->create();

        $response = $this->patch(route('persons.archive', $person));

        $response->assertRedirect(route('persons.index'));
        $this->assertDatabaseHas('ENT_persons', ['id' => $person->id, 'active' => false]);
    }

    public function test_can_activate_person(): void
    {
        $person = Person::factory()->archived()->create();

        $response = $this->patch(route('persons.archived.activate', $person));

        $response->assertRedirect(route('persons.archived.index'));
        $this->assertDatabaseHas('ENT_persons', ['id' => $person->id, 'active' => true]);
    }
}
