<?php

namespace Entities13\Tests\Feature\PersonTypes;

use Entities13\Models\Person;
use Entities13\Tests\TestCase;
use Entities13\Models\PersonType;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PersonTypeCrudTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs($this->createAdminUser());
    }

    public function test_can_list_person_types(): void
    {
        PersonType::factory()->count(3)->create();

        $response = $this->get(route('person-types.index'));

        $response->assertOk();
        $response->assertSee('Person types');
    }

    public function test_can_create_person_type_with_euskeran_only(): void
    {
        $response = $this->post(route('person-types.store'), [
            'name_eu' => 'Mota bakarra',
        ]);

        $response->assertRedirect(route('person-types.index'));
        $this->assertDatabaseHas('ENT_person_types', [
            'name_eu' => 'Mota bakarra',
            'name_es' => null,
            'name_fr' => null,
            'name_en' => null,
        ]);
    }

    public function test_can_create_person_type_with_all_languages(): void
    {
        $response = $this->post(route('person-types.store'), [
            'name_eu' => 'Mota multi',
            'name_es' => 'Tipo multi',
            'name_fr' => 'Type multi',
            'name_en' => 'Multi type',
        ]);

        $response->assertRedirect(route('person-types.index'));
        $this->assertDatabaseHas('ENT_person_types', [
            'name_eu' => 'Mota multi',
            'name_es' => 'Tipo multi',
            'name_fr' => 'Type multi',
            'name_en' => 'Multi type',
        ]);
    }

    public function test_can_update_person_type(): void
    {
        $type = PersonType::factory()->create(['name_eu' => 'Original']);

        $response = $this->put(route('person-types.update', $type), [
            'name_eu' => 'Actualizado',
            'name_es' => $type->name_es,
            'name_fr' => $type->name_fr,
            'name_en' => $type->name_en,
        ]);

        $response->assertRedirect(route('person-types.index'));
        $this->assertDatabaseHas('ENT_person_types', ['id' => $type->id, 'name_eu' => 'Actualizado']);
    }

    public function test_can_delete_person_type(): void
    {
        $type = PersonType::factory()->create();

        $response = $this->delete(route('person-types.destroy', $type));

        $response->assertRedirect(route('person-types.index'));
        $this->assertSoftDeleted('ENT_person_types', ['id' => $type->id]);
    }

    public function test_cannot_delete_person_type_in_use(): void
    {
        $type = PersonType::factory()->create();
        Person::factory()->withTypes([$type])->create();

        $response = $this->delete(route('person-types.destroy', $type));

        $response->assertRedirect(route('person-types.index'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('ENT_person_types', ['id' => $type->id, 'deleted_at' => null]);
    }

    public function test_cannot_delete_person_type_used_only_by_trashed_person(): void
    {
        $type = PersonType::factory()->create();
        Person::factory()->withTypes([$type])->create()->delete();

        $response = $this->delete(route('person-types.destroy', $type));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('ENT_person_types', ['id' => $type->id, 'deleted_at' => null]);
    }

    public function test_cannot_create_person_type_with_duplicate_name_eu(): void
    {
        PersonType::factory()->create(['name_eu' => 'Duplicado']);

        $response = $this->post(route('person-types.store'), [
            'name_eu' => 'Duplicado',
        ]);

        $response->assertSessionHasErrors('name_eu');
    }

    public function test_a_name_can_repeat_in_another_language(): void
    {
        PersonType::factory()->create(['name_eu' => 'Mota A', 'name_es' => 'Tipo Común']);
        $response = $this->post(route('person-types.store'), [
            'name_eu' => 'Tipo Común',
            'name_es' => 'Tipo B',
        ]);

        $response->assertRedirect(route('person-types.index'));
    }

    public function test_trashed_person_type_conflict_shows_modal(): void
    {
        $trashed = PersonType::factory()->create(['name_eu' => 'Conflict Type'])->delete();

        $response = $this->post(route('person-types.store'), [
            'name_eu' => 'Conflict Type',
        ]);

        $response->assertRedirect(route('person-types.index'));
        $response->assertSessionHas('deleted_person_type_conflict');
    }
}
