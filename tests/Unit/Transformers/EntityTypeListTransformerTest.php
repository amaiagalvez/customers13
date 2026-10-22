<?php

namespace Entities13\Tests\Unit\Transformers;

use Basics13\Tests\TestCase;
use Entities13\Models\EntityType;
use Illuminate\Support\Facades\Route;
use Illuminate\Pagination\LengthAwarePaginator;
use Entities13\Transformers\EntityTypeListTransformer;

final class EntityTypeListTransformerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('entity-types', fn () => null)->name('entity-types.index');
        Route::get('entity-types/archived', fn () => null)->name('entity-types.archived.index');
        Route::get('entity-types/trash', fn () => null)->name('entity-types.trash.index');
        Route::delete('entity-types/{record}', fn () => null)->name('entity-types.destroy');
        Route::patch('entity-types/{record}/archive', fn () => null)->name('entity-types.archive');
        Route::patch('entity-types/archived/{record}', fn () => null)->name('entity-types.archived.activate');
        Route::patch('entity-types/trash/{record}', fn () => null)->name('entity-types.trash.restore');
        Route::delete('entity-types/trash/{record}', fn () => null)->name('entity-types.trash.destroy');
    }

    public function test_confirmation_names_the_type_by_its_basque_name(): void
    {
        $type = EntityType::factory()->make(['id' => 1, 'name_eu' => 'Ikastetxea', 'name_es' => 'Colegio']);
        $type->setAttribute('entities_count', 0);

        $rows = (new EntityTypeListTransformer)->active(new LengthAwarePaginator([$type], 1, 15), '')['rows'];
        $delete = collect($rows[0]['actions'])->firstWhere('test', 'entity-type-delete-1');

        $this->assertSame(__('basics13::messages.delete_record', ['name' => 'Ikastetxea']), $delete['confirmTitle']);
    }
}
