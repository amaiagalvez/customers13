<?php

namespace entities13\Tests\Unit\Transformers;

use Basics13\Tests\TestCase;
use entities13\Models\Entity;
use Illuminate\Support\Facades\Route;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use entities13\Transformers\EntityListTransformer;

final class EntityListTransformerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Route::get('dashboard', fn() => null)->name('dashboard');
        Route::get('entities', fn() => null)->name('entities.index');
        Route::get('entities/archived', fn() => null)->name('entities.archived.index');
        Route::get('entities/trash', fn() => null)->name('entities.trash.index');
        Route::delete('entities/{record}', fn() => null)->name('entities.destroy');
        Route::patch('entities/{record}/archive', fn() => null)->name('entities.archive');
        Route::patch('entities/archived/{record}', fn() => null)->name('entities.archived.activate');
        Route::patch('entities/trash/{record}', fn() => null)->name('entities.trash.restore');
        Route::delete('entities/trash/{record}', fn() => null)->name('entities.trash.destroy');
    }

    public function test_active_list_without_integration_allows_deleting_entity(): void
    {
        $transformer = new EntityListTransformer;

        $result = $transformer->active($this->records(), '');
        $actions = $result['rows'][0]['actions'];

        $this->assertSame('confirm-modal', $actions[1]['type']);
        $this->assertSame('entity-delete-1', $actions[1]['test']);
    }

    public function test_trash_list_without_integration_allows_permanent_deletion(): void
    {
        $transformer = new EntityListTransformer;
        $result = $transformer->trash($this->records(), '');
        $actions = $result['rows'][0]['actions'];

        $this->assertSame(['entity-restore-1', 'entity-force-delete-1'], array_column($actions, 'test'));
    }

    public function test_trash_list_shows_the_hint_when_permanent_deletion_is_blocked(): void
    {
        $transformer = new BlockingEntityListTransformer;
        $result = $transformer->trash($this->records(), '');
        $blockedAction = $result['rows'][0]['actions'][1];

        $this->assertSame('blocked', $blockedAction['type']);
        $this->assertSame('The record is referenced by other data.', $blockedAction['hint']);
    }

    public function test_trash_list_uses_an_empty_hint_when_no_force_delete_hint_is_defined(): void
    {
        $transformer = new BlockingForceDeleteEntityListTransformer;
        $result = $transformer->trash($this->records(), '');
        $blockedAction = $result['rows'][0]['actions'][1];

        $this->assertSame('blocked', $blockedAction['type']);
        $this->assertSame('', $blockedAction['hint']);
    }

    /**
     * @return LengthAwarePaginator<int, Entity>
     */
    private function records(): LengthAwarePaginator
    {
        $record = Entity::factory()->make(['id' => 1]);
        $record->setRelation('entityTypes', collect());

        return new LengthAwarePaginator([$record], 1, 15);
    }
}

final class BlockingEntityListTransformer extends EntityListTransformer
{
    protected function getDeleteBlockCondition(Model $record): bool
    {
        return true;
    }

    protected function getForceDeleteBlockCondition(Model $record): bool
    {
        return true;
    }

    protected function getForceDeleteBlockHint(Model $record): string
    {
        return 'The record is referenced by other data.';
    }
}

final class BlockingForceDeleteEntityListTransformer extends EntityListTransformer
{
    protected function getForceDeleteBlockCondition(Model $record): bool
    {
        return true;
    }
}
