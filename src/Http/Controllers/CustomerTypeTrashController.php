<?php

namespace entities13\Http\Controllers;

use Illuminate\View\View;
use entities13\Models\EntityType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Controllers\TrashController;
use entities13\Http\Requests\EntityTypeListRequest;
use entities13\Http\Requests\EntityTypeDestroyRequest;
use entities13\Http\Requests\EntityTypeRestoreRequest;
use entities13\Transformers\EntityTypeListTransformer;
use entities13\Queries\EntityTypes\EntityTypeListQuery;

/**
 * @extends TrashController<EntityType>
 */
class EntityTypeTrashController extends TrashController
{
    public function index(
        EntityTypeListRequest $request,
        EntityTypeListQuery $query,
        EntityTypeListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $entityTypes = $query->trashed($search);

        return $this->listView($request, 'entities13::entity-types.list', [
            'entityTypes' => $entityTypes,
            'list' => $transformer->trash(
                $entityTypes,
                $search,
                $query->stateCounts(trashedTotal: $search === '' ? $entityTypes->total() : null),
            ),
        ]);
    }

    public function restore(EntityTypeRestoreRequest $request): RedirectResponse
    {
        return $this->restoreTrashed($request);
    }

    public function destroy(EntityTypeDestroyRequest $request): RedirectResponse
    {
        return $this->destroyTrashed($request);
    }

    /**
     * @param  EntityType  $record
     */
    protected function restoreTrashedRecord(Model $record): void
    {
        $record->restore();
    }

    /**
     * A type is named in four languages and restoring it puts all four back, so any of them being
     * taken by a live type makes the restore impossible.
     *
     * @param  EntityType  $record
     */
    protected function nameIsTaken(Model $record): bool
    {
        foreach (EntityType::NAME_FIELDS as $column) {
            $name = $record->getAttribute($column);

            if ($name !== null && EntityType::query()->where($column, $name)->exists()) {
                return true;
            }
        }

        return false;
    }

    protected function recordClass(): string
    {
        return EntityType::class;
    }

    protected function trashRoute(): string
    {
        return 'entity-types.trash.index';
    }
}
