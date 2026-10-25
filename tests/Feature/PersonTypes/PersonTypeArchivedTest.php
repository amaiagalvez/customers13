<?php

namespace Entities13\Tests\Feature\PersonTypes;

use Entities13\Tests\TestCase;
use Entities13\Models\PersonType;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PersonTypeArchivedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->createAdminUser());
    }

    public function test_can_view_archived_person_types(): void
    {
        $archived = PersonType::factory()->archived()->create();

        $response = $this->get(route('person-types.archived.index'));

        $response->assertOk();
        $response->assertSee($archived->name_eu);
    }

    public function test_can_archive_person_type(): void
    {
        $type = PersonType::factory()->create();

        $response = $this->patch(route('person-types.archive', $type));

        $response->assertRedirect(route('person-types.index'));
        $this->assertDatabaseHas('ENT_person_types', ['id' => $type->id, 'active' => false]);
    }

    public function test_can_activate_person_type(): void
    {
        $type = PersonType::factory()->archived()->create();

        $response = $this->patch(route('person-types.archived.activate', $type));

        $response->assertRedirect(route('person-types.archived.index'));
        $this->assertDatabaseHas('ENT_person_types', ['id' => $type->id, 'active' => true]);
    }
}
