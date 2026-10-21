<?php

namespace Entities13\Http\Controllers;

use Illuminate\View\View;
use Entities13\Models\Entity;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Controllers\TrashController;
use Entities13\Http\Requests\EntityListRequest;
use Entities13\Queries\Entities\EntityListQuery;
use Entities13\Http\Requests\EntityDestroyRequest;
use Entities13\Http\Requests\EntityRestoreRequest;
use Entities13\Transformers\EntityListTransformer;

/**
 * @extends TrashController<Entity>
 */
class EntityTrashController extends TrashController
{
    public function index(
        EntityListRequest $request,
        EntityListQuery $query,
        EntityListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $filters = $request->filters() ?? [];
        $entities = $query->trashed($search, $filters);

        return $this->listView($request, 'entities13::entities.list', [
            'entities' => $entities,
            'list' => $transformer->trash(
                $entities,
                $search,
                $query->stateCounts(trashedTotal: $search === '' ? $entities->total() : null),
            ),
            'searchableFields' => $query->searchableFields('trashed'),
            'filters' => $filters,
            'scope' => 'trashed',
        ]);
    }

    public function restore(EntityRestoreRequest $request): RedirectResponse
    {
        return $this->restoreTrashed($request);
    }

    public function destroy(EntityDestroyRequest $request): RedirectResponse
    {
        return $this->destroyTrashed($request);
    }

    /**
     * @param  Entity  $record
     */
    protected function restoreTrashedRecord(Model $record): void
    {
        $record->restore();
    }

    /**
     * @param  Entity  $record
     */
    protected function nameIsTaken(Model $record): bool
    {
        return $this->takenBy(Entity::query(), $record->name);
    }

    protected function recordClass(): string
    {
        return Entity::class;
    }

    protected function trashRoute(): string
    {
        return 'entities.trash.index';
    }
}
