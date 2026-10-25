<?php

namespace Entities13\Tests\Unit\Transformers;

use Basics13\Tests\TestCase;
use Entities13\Models\PersonType;
use Illuminate\Support\Facades\Route;
use Illuminate\Pagination\LengthAwarePaginator;
use Entities13\Transformers\PersonTypeListTransformer;

final class PersonTypeListTransformerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('person-types', fn () => null)->name('person-types.index');
        Route::get('person-types/archived', fn () => null)->name('person-types.archived.index');
        Route::get('person-types/trash', fn () => null)->name('person-types.trash.index');
        Route::delete('person-types/{record}', fn () => null)->name('person-types.destroy');
        Route::patch('person-types/{record}/archive', fn () => null)->name('person-types.archive');
        Route::patch('person-types/archived/{record}', fn () => null)->name('person-types.archived.activate');
        Route::patch('person-types/trash/{record}', fn () => null)->name('person-types.trash.restore');
        Route::delete('person-types/trash/{record}', fn () => null)->name('person-types.trash.destroy');
    }

    public function test_row_carries_the_name_in_every_language(): void
    {
        $row = (new PersonTypeListTransformer)->active($this->records(0), '')['rows'][0];

        $this->assertSame(['Bazkidea', 'Socio', 'Membre', 'Member'], [$row['nameEu'], $row['nameEs'], $row['nameFr'], $row['nameEn']]);
        $this->assertSame(['id' => 1, 'name_eu' => 'Bazkidea', 'name_es' => 'Socio', 'name_fr' => 'Membre', 'name_en' => 'Member', 'notes' => null], $row['editPayload']);
    }

    public function test_confirmation_names_the_type_by_its_basque_name(): void
    {
        $rows = (new PersonTypeListTransformer)->active($this->records(0), '')['rows'];
        $delete = array_column($rows[0]['actions'], null, 'test')['person-type-delete-1'];

        $this->assertSame(__('basics13::messages.delete_record', ['name' => 'Bazkidea']), $delete['confirmTitle']);
    }

    public function test_a_type_in_use_is_archived_instead_of_deleted(): void
    {
        $transformer = new PersonTypeListTransformer;

        $this->assertSame(['person-type-edit-1', 'person-type-delete-1'], array_column($transformer->active($this->records(0), '')['rows'][0]['actions'], 'test'));
        $this->assertSame(['person-type-edit-1', 'person-type-archive-1'], array_column($transformer->active($this->records(1), '')['rows'][0]['actions'], 'test'));
    }

    public function test_a_type_in_use_cannot_be_deleted_permanently(): void
    {
        $transformer = new PersonTypeListTransformer;

        $this->assertSame(['person-type-restore-1', 'person-type-force-delete-1'], array_column($transformer->trash($this->records(0), '')['rows'][0]['actions'], 'test'));

        $blocked = $transformer->trash($this->records(1), '')['rows'][0]['actions'][1];

        $this->assertSame('blocked', $blocked['type']);
        $this->assertSame(__('basics13::messages.cannot_force_delete_related'), $blocked['hint']);
    }

    /**
     * @return LengthAwarePaginator<int, PersonType>
     */
    private function records(int $personsCount): LengthAwarePaginator
    {
        $type = PersonType::factory()->make(['id' => 1, 'name_eu' => 'Bazkidea', 'name_es' => 'Socio', 'name_fr' => 'Membre', 'name_en' => 'Member', 'notes' => null]);
        $type->setAttribute('persons_count', $personsCount);

        return new LengthAwarePaginator([$type], 1, 15);
    }
}
