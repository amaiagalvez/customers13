<?php

namespace Entities13\Http\Controllers;

use Illuminate\View\View;
use Entities13\Models\Person;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Controllers\TrashController;
use Entities13\Http\Requests\PersonListRequest;
use Entities13\Queries\Persons\PersonListQuery;
use Entities13\Http\Requests\PersonDestroyRequest;
use Entities13\Http\Requests\PersonRestoreRequest;
use Entities13\Transformers\PersonListTransformer;

/**
 * @extends TrashController<Person>
 */
class PersonTrashController extends TrashController
{
    public function index(
        PersonListRequest $request,
        PersonListQuery $query,
        PersonListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $filters = $request->filters() ?? [];
        $persons = $query->trashed($search, $filters);

        return $this->listView($request, 'entities13::persons.list', [
            'persons' => $persons,
            'list' => $transformer->trash(
                $persons,
                $search,
                $query->stateCounts(trashedTotal: $search === '' && $filters === [] ? $persons->total() : null),
            ),
            'searchableFields' => $query->searchableFields('trashed'),
            'filters' => $filters,
            'scope' => 'trashed',
        ]);
    }

    public function restore(PersonRestoreRequest $request): RedirectResponse
    {
        return $this->restoreTrashed($request);
    }

    public function destroy(PersonDestroyRequest $request): RedirectResponse
    {
        return $this->destroyTrashed($request);
    }

    /**
     * @param  Person  $record
     */
    protected function restoreTrashedRecord(Model $record): void
    {
        $record->restore();
    }

    protected function nameIsTaken(Model $record): bool
    {
        // Persons don't have unique names, so no name conflict on restore
        return false;
    }

    protected function recordClass(): string
    {
        return Person::class;
    }

    protected function trashRoute(): string
    {
        return 'persons.trash.index';
    }
}
