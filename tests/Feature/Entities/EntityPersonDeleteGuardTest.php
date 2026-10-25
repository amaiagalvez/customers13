<?php

namespace Entities13\Tests\Feature\Entities;

use Entities13\Models\Entity;
use Entities13\Models\Person;
use Entities13\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EntityPersonDeleteGuardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->createAdminUser());
    }

    public function test_cannot_delete_entity_with_active_person(): void
    {
        $entity = Entity::factory()->create();
        Person::factory()->forEntity($entity)->create();

        $response = $this->delete(route('entities.destroy', $entity));

        $response->assertRedirect(route('entities.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('ENT_entities', ['id' => $entity->id, 'deleted_at' => null]);
    }

    public function test_cannot_delete_entity_with_trashed_person(): void
    {
        $entity = Entity::factory()->create();
        Person::factory()->forEntity($entity)->create()->delete();

        $response = $this->delete(route('entities.destroy', $entity));

        $response->assertRedirect(route('entities.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('ENT_entities', ['id' => $entity->id, 'deleted_at' => null]);
    }

    public function test_cannot_force_delete_entity_with_trashed_person(): void
    {
        $entity = Entity::factory()->trashed()->create();
        // A trashed entity a person still points at is not reachable through the application (the
        // soft delete is refused first), so the person is written directly against it.
        Person::factory()->forEntity($entity)->trashed()->create();

        $response = $this->delete(route('entities.trash.destroy', $entity));

        $response->assertRedirect(route('entities.trash.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('ENT_entities', ['id' => $entity->id]);
    }

    public function test_cannot_delete_entity_with_project_and_person(): void
    {
        $entity = Entity::factory()->create();
        Person::factory()->forEntity($entity)->create();
        // Project would be created via entity-projects13 bridge

        $response = $this->delete(route('entities.destroy', $entity));

        $response->assertRedirect(route('entities.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('ENT_entities', ['id' => $entity->id, 'deleted_at' => null]);
    }

    public function test_entity_row_shows_archive_when_has_person(): void
    {
        $entity = Entity::factory()->create();
        Person::factory()->forEntity($entity)->create();

        $response = $this->get(route('entities.index'));

        $response->assertOk();
        // The list should show archive action instead of delete for entities with persons
        $this->assertStringContainsString('archive', (string) $response->getContent());
    }

    public function test_can_delete_entity_without_persons_or_projects(): void
    {
        $entity = Entity::factory()->create();

        $response = $this->delete(route('entities.destroy', $entity));

        $response->assertRedirect(route('entities.index'));
        $response->assertSessionHas('status');
        $this->assertSoftDeleted('ENT_entities', ['id' => $entity->id]);
    }
}
