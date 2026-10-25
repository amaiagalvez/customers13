<?php

namespace Entities13\Tests\Feature\Persons;

use Entities13\Models\Entity;
use Entities13\Models\Person;
use Entities13\Tests\TestCase;
use Entities13\Models\PersonType;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PersonCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->createAdminUser());
    }

    public function test_can_list_persons(): void
    {
        Person::factory()->count(3)->create();

        $response = $this->get(route('persons.index'));

        $response->assertOk();
        $response->assertSee('Persons');
    }

    public function test_can_create_person_without_entity(): void
    {
        $response = $this->post(route('persons.store'), [
            'name' => 'Leire',
            'last_name_1' => 'Sánchez',
            'last_name_2' => 'Pérez',
            'document_number' => '12345678A',
            'lang' => 'eu',
        ]);

        $response->assertRedirect(route('persons.index'));
        $this->assertDatabaseHas('ENT_persons', [
            'name' => 'Leire',
            'entity_id' => null,
        ]);
    }

    public function test_can_create_person_with_entity(): void
    {
        $entity = Entity::factory()->create();
        $response = $this->post(route('persons.store'), [
            'name' => 'Ander',
            'last_name_1' => 'García',
            'last_name_2' => 'López',
            'document_number' => '87654321B',
            'lang' => 'es',
            'entity_id' => $entity->id,
        ]);

        $response->assertRedirect(route('persons.index'));
        $this->assertDatabaseHas('ENT_persons', [
            'name' => 'Ander',
            'entity_id' => $entity->id,
        ]);
    }

    public function test_can_create_person_with_person_types(): void
    {
        $types = PersonType::factory()->count(2)->create();
        $response = $this->post(route('persons.store'), [
            'name' => 'Jon',
            'last_name_1' => 'Fernández',
            'last_name_2' => 'González',
            'person_type_ids' => $types->pluck('id')->toArray(),
        ]);

        $response->assertRedirect(route('persons.index'));
        $person = Person::where('name', 'Jon')->first();
        $this->assertCount(2, $person->personTypes);
    }

    public function test_can_update_person(): void
    {
        $person = Person::factory()->create(['name' => 'Original']);

        $response = $this->put(route('persons.update', $person), [
            'name' => 'Updated',
            'last_name_1' => $person->last_name_1,
            'last_name_2' => $person->last_name_2,
        ]);

        $response->assertRedirect(route('persons.index'));
        $this->assertDatabaseHas('ENT_persons', ['id' => $person->id, 'name' => 'Updated']);
    }

    public function test_can_update_person_keeping_archived_entity(): void
    {
        $entity = Entity::factory()->archived()->create();
        $person = Person::factory()->forEntity($entity)->create();

        $response = $this->put(route('persons.update', $person), [
            'name' => $person->name,
            'last_name_1' => $person->last_name_1,
            'last_name_2' => $person->last_name_2,
            'entity_id' => $entity->id,
        ]);

        $response->assertRedirect(route('persons.index'));
        $this->assertDatabaseHas('ENT_persons', ['id' => $person->id, 'entity_id' => $entity->id]);
    }

    public function test_can_delete_person(): void
    {
        $person = Person::factory()->create();

        $response = $this->delete(route('persons.destroy', $person));

        $response->assertRedirect(route('persons.index'));
        $this->assertSoftDeleted('ENT_persons', ['id' => $person->id]);
    }

    public function test_cannot_create_person_without_name(): void
    {
        $response = $this->post(route('persons.store'), [
            'last_name_1' => 'Sánchez',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_cannot_create_person_with_invalid_lang(): void
    {
        $response = $this->post(route('persons.store'), [
            'name' => 'Test',
            'lang' => 'xx',
        ]);

        $response->assertSessionHasErrors('lang');
    }
}
