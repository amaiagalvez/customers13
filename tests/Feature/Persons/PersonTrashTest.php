<?php

namespace Entities13\Tests\Feature\Persons;

use Entities13\Models\Person;
use Entities13\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PersonTrashTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->createAdminUser());
    }

    public function test_can_view_trashed_persons(): void
    {
        $trashed = Person::factory()->trashed()->create();

        $response = $this->get(route('persons.trash.index'));

        $response->assertOk();
        $response->assertSee($trashed->getFullName());
    }

    public function test_can_restore_person(): void
    {
        $trashed = Person::factory()->trashed()->create();

        $response = $this->patch(route('persons.trash.restore', $trashed));

        $response->assertRedirect(route('persons.trash.index'));
        $this->assertDatabaseHas('ENT_persons', ['id' => $trashed->id, 'deleted_at' => null]);
    }

    public function test_can_force_delete_person(): void
    {
        $trashed = Person::factory()->trashed()->create();

        $response = $this->delete(route('persons.trash.destroy', $trashed));

        $response->assertRedirect(route('persons.trash.index'));
        $this->assertDatabaseMissing('ENT_persons', ['id' => $trashed->id]);
    }
}
