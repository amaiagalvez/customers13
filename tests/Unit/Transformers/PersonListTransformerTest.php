<?php

namespace Entities13\Tests\Unit\Transformers;

use Basics13\Tests\TestCase;
use Entities13\Models\Entity;
use Entities13\Models\Person;
use Entities13\Models\PersonType;
use Illuminate\Support\Facades\Route;
use Illuminate\Pagination\LengthAwarePaginator;
use Entities13\Transformers\PersonListTransformer;

final class PersonListTransformerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('persons', fn () => null)->name('persons.index');
        Route::get('persons/archived', fn () => null)->name('persons.archived.index');
        Route::get('persons/trash', fn () => null)->name('persons.trash.index');
        Route::delete('persons/{record}', fn () => null)->name('persons.destroy');
        Route::patch('persons/{record}/archive', fn () => null)->name('persons.archive');
        Route::patch('persons/archived/{record}', fn () => null)->name('persons.archived.activate');
        Route::patch('persons/trash/{record}', fn () => null)->name('persons.trash.restore');
        Route::delete('persons/trash/{record}', fn () => null)->name('persons.trash.destroy');
    }

    public function test_row_carries_the_person_columns_its_entity_and_types(): void
    {
        $entity = Entity::factory()->make(['id' => 7, 'name' => 'Test Entity']);
        $person = $this->person(['name' => 'Ander', 'last_name_1' => 'García', 'last_name_2' => 'López', 'document_number' => '12345678A', 'lang' => 'eu'], $entity, 2);

        $row = (new PersonListTransformer)->active($this->records($person), '')['rows'][0];

        $this->assertSame('Ander', $row['name']);
        $this->assertSame('García', $row['last_name_1']);
        $this->assertSame('López', $row['last_name_2']);
        $this->assertSame('12345678A', $row['document_number']);
        $this->assertSame('eu', $row['lang']);
        $this->assertSame(['id' => 7, 'name' => 'Test Entity'], $row['entity']);
        $this->assertCount(2, $row['personTypes']);
    }

    public function test_row_without_entity_has_a_null_entity(): void
    {
        $row = (new PersonListTransformer)->active($this->records($this->person()), '')['rows'][0];

        $this->assertNull($row['entity']);
    }

    public function test_confirmation_names_the_person_by_the_full_name(): void
    {
        $person = $this->person(['name' => 'Ander', 'last_name_1' => 'García', 'last_name_2' => 'López']);

        $rows = (new PersonListTransformer)->active($this->records($person), '')['rows'];
        $delete = array_column($rows[0]['actions'], null, 'test')['person-delete-1'];

        $this->assertSame(__('basics13::messages.delete_record', ['name' => 'Ander García López']), $delete['confirmTitle']);
    }

    public function test_a_person_can_always_be_deleted_and_force_deleted(): void
    {
        $transformer = new PersonListTransformer;

        $this->assertSame(['person-edit-1', 'person-delete-1'], array_column($transformer->active($this->records($this->person()), '')['rows'][0]['actions'], 'test'));
        $this->assertSame(['person-restore-1', 'person-force-delete-1'], array_column($transformer->trash($this->records($this->person()), '')['rows'][0]['actions'], 'test'));
    }

    public function test_edit_payload_lists_the_type_ids_as_strings(): void
    {
        $row = (new PersonListTransformer)->active($this->records($this->person([], null, 2)), '')['rows'][0];

        $this->assertSame(['1', '2'], $row['editPayload']['person_type_ids']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function person(array $attributes = [], ?Entity $entity = null, int $types = 0): Person
    {
        $person = Person::factory()->make(['id' => 1, 'entity_id' => $entity?->id, ...$attributes]);
        $person->setRelation('entity', $entity);
        $person->setRelation('personTypes', collect($types > 0 ? range(1, $types) : [])->map(
            static fn (int $id): PersonType => PersonType::factory()->make(['id' => $id]),
        ));

        return $person;
    }

    /**
     * @return LengthAwarePaginator<int, Person>
     */
    private function records(Person $person): LengthAwarePaginator
    {
        return new LengthAwarePaginator([$person], 1, 15);
    }

    public function test_a_person_has_no_force_delete_hint(): void
    {
        $transformer = new PersonListTransformer;

        $this->assertSame('', (new \ReflectionMethod($transformer, 'getForceDeleteBlockHint'))->invoke($transformer, new Person));
    }
}
