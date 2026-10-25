<?php

namespace Entities13\Tests\Feature\PersonTypes;

use Entities13\Models\Person;
use Entities13\Tests\TestCase;
use Entities13\Models\PersonType;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PersonTypeTrashTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->createAdminUser());
    }

    public function test_can_view_trashed_person_types(): void
    {
        $trashed = PersonType::factory()->trashed()->create();

        $response = $this->get(route('person-types.trash.index'));

        $response->assertOk();
        $response->assertSee($trashed->name_eu);
    }

    public function test_can_restore_person_type(): void
    {
        $trashed = PersonType::factory()->trashed()->create();

        $response = $this->patch(route('person-types.trash.restore', $trashed));

        $response->assertRedirect(route('person-types.trash.index'));
        $this->assertDatabaseHas('ENT_person_types', ['id' => $trashed->id, 'deleted_at' => null]);
    }

    public function test_cannot_restore_person_type_with_taken_name(): void
    {
        $trashed = PersonType::factory()->trashed()->create(['name_eu' => 'Taken Name']);
        PersonType::factory()->create(['name_eu' => 'Taken Name']);

        $response = $this->patch(route('person-types.trash.restore', $trashed));

        $response->assertRedirect(route('person-types.trash.index'));
        $response->assertSessionHas('error');
        $this->assertSoftDeleted('ENT_person_types', ['id' => $trashed->id]);
    }

    public function test_can_force_delete_person_type(): void
    {
        $trashed = PersonType::factory()->trashed()->create();

        $response = $this->delete(route('person-types.trash.destroy', $trashed));

        $response->assertRedirect(route('person-types.trash.index'));
        $this->assertDatabaseMissing('ENT_person_types', ['id' => $trashed->id]);
    }

    public function test_cannot_force_delete_person_type_in_use_by_trashed_person(): void
    {
        $type = PersonType::factory()->trashed()->create();
        // A trashed type a person still points at is not reachable through the application (the
        // soft delete is refused first), so the pivot row is written directly to exercise the
        // foreign key that backstops the guard.
        Person::factory()->trashed()->create()->personTypes()->attach($type);

        $response = $this->delete(route('person-types.trash.destroy', $type));

        $response->assertRedirect(route('person-types.trash.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('ENT_person_types', ['id' => $type->id]);
    }
}
