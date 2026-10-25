<?php

namespace Entities13\Tests\Feature\PersonTypes;

use Entities13\Models\Person;
use Entities13\Tests\TestCase;
use Entities13\Models\PersonType;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PersonTypeMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_person_type_table_has_expected_columns(): void
    {
        $this->assertTrue(\Schema::hasTable('ENT_person_types'));
        $this->assertTrue(\Schema::hasColumns('ENT_person_types', [
            'id', 'name_eu', 'name_es', 'name_fr', 'name_en', 'notes',
            'active', 'created_at', 'updated_at', 'deleted_at', 'created_by', 'updated_by', 'deleted_by',
        ]));
    }

    public function test_person_type_pivot_table_has_expected_columns(): void
    {
        $this->assertTrue(\Schema::hasTable('ENT_person_person_type'));
        $this->assertTrue(\Schema::hasColumns('ENT_person_person_type', [
            'person_id', 'person_type_id', 'created_at', 'updated_at',
        ]));
    }

    public function test_person_type_name_eu_is_required(): void
    {
        $this->expectException(QueryException::class);
        PersonType::factory()->create(['name_eu' => null]);
    }

    public function test_person_type_name_eu_is_unique_among_active(): void
    {
        PersonType::factory()->create(['name_eu' => 'Test Type']);

        $this->expectException(QueryException::class);
        PersonType::factory()->create(['name_eu' => 'Test Type']);
    }

    public function test_person_type_name_can_repeat_in_different_language(): void
    {
        PersonType::factory()->create(['name_eu' => 'Mota komuna', 'name_es' => 'Tipo A']);
        PersonType::factory()->create(['name_eu' => 'Type B', 'name_es' => 'Mota komuna']);

        $this->assertCount(2, PersonType::all());
    }

    public function test_person_type_cannot_be_deleted_when_in_use_by_person(): void
    {
        $type = PersonType::factory()->create();
        Person::factory()->withTypes([$type])->create();

        $this->assertFalse($type->delete());
    }

    public function test_person_type_can_be_deleted_when_not_in_use(): void
    {
        $type = PersonType::factory()->create();
        $this->assertTrue($type->delete());
    }

    public function test_person_type_cannot_be_deleted_when_person_is_trashed(): void
    {
        $type = PersonType::factory()->create();
        Person::factory()->withTypes([$type])->create()->delete();

        $this->assertFalse($type->delete());
    }

    public function test_a_name_is_taken_only_by_a_type_out_of_the_trash(): void
    {
        PersonType::factory()->create(['name_eu' => 'Another Type', 'name_es' => 'Otro tipo']);
        PersonType::factory()->create(['name_eu' => 'Unique Type'])->delete();

        $this->assertTrue(PersonType::nameIsTaken('Otro tipo'));
        $this->assertFalse(PersonType::nameIsTaken('Unique Type'));
    }
}
