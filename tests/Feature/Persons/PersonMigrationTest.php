<?php

namespace Entities13\Tests\Feature\Persons;

use Entities13\Models\Entity;
use Entities13\Models\Person;
use Entities13\Tests\TestCase;
use Entities13\Models\PersonType;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PersonMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_person_table_has_expected_columns(): void
    {
        $this->assertTrue(\Schema::hasTable('ENT_persons'));
        $this->assertTrue(\Schema::hasColumns('ENT_persons', [
            'id', 'name', 'last_name_1', 'last_name_2', 'document_number', 'lang', 'entity_id', 'notes',
            'active', 'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'deleted_by',
        ]));
    }

    public function test_person_name_is_required(): void
    {
        $person = Person::factory()->make(['name' => null]);

        $this->assertFalse($person->isValid());
    }

    public function test_person_can_exist_without_entity(): void
    {
        $person = Person::factory()->create(['entity_id' => null]);

        $this->assertNull($person->entity_id);
        $this->assertNull($person->entity);
    }

    public function test_person_can_have_entity(): void
    {
        $entity = Entity::factory()->create();
        $person = Person::factory()->forEntity($entity)->create();

        $this->assertEquals($entity->id, $person->entity_id);
        $this->assertEquals($entity->id, $person->entity->id);
    }

    public function test_person_can_have_multiple_person_types(): void
    {
        $types = PersonType::factory()->count(3)->create();
        $person = Person::factory()->withTypes($types)->create();

        $this->assertCount(3, $person->personTypes);
    }

    public function test_person_full_name_composition(): void
    {
        $person = new Person([
            'name' => 'Ander',
            'last_name_1' => 'García',
            'last_name_2' => 'López',
        ]);

        $this->assertEquals('Ander García López', $person->getFullName());
    }

    public function test_person_full_name_with_partial_data(): void
    {
        $person = new Person([
            'name' => 'Maria',
            'last_name_1' => 'Rodríguez',
            'last_name_2' => null,
        ]);

        $this->assertEquals('Maria Rodríguez', $person->getFullName());
    }
}
