<?php

namespace Entities13\Http\Controllers;

use Illuminate\View\View;
use Entities13\Models\PersonType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Controllers\TrashController;
use Entities13\Http\Requests\PersonTypeListRequest;
use Entities13\Http\Requests\PersonTypeDestroyRequest;
use Entities13\Http\Requests\PersonTypeRestoreRequest;
use Entities13\Transformers\PersonTypeListTransformer;
use Entities13\Queries\PersonTypes\PersonTypeListQuery;

/**
 * @extends TrashController<PersonType>
 */
class PersonTypeTrashController extends TrashController
{
    public function index(
        PersonTypeListRequest $request,
        PersonTypeListQuery $query,
        PersonTypeListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $personTypes = $query->trashed($search);

        return $this->listView($request, 'entities13::person-types.list', [
            'personTypes' => $personTypes,
            'list' => $transformer->trash(
                $personTypes,
                $search,
                $query->stateCounts(trashedTotal: $search === '' ? $personTypes->total() : null),
            ),
            'scope' => 'trashed',
        ]);
    }

    public function restore(PersonTypeRestoreRequest $request): RedirectResponse
    {
        return $this->restoreTrashed($request);
    }

    public function destroy(PersonTypeDestroyRequest $request): RedirectResponse
    {
        return $this->destroyTrashed($request);
    }

    /**
     * @param  PersonType  $record
     */
    protected function restoreTrashedRecord(Model $record): void
    {
        $record->restore();
    }

    /**
     * A type is named in four languages and restoring it puts all four back, so any of them being
     * taken by a live type makes the restore impossible.
     *
     * @param  PersonType  $record
     */
    protected function nameIsTaken(Model $record): bool
    {
        foreach (PersonType::NAME_FIELDS as $column) {
            $name = $record->getAttribute($column);

            if ($name !== null && PersonType::query()->where($column, $name)->exists()) {
                return true;
            }
        }

        return false;
    }

    protected function recordClass(): string
    {
        return PersonType::class;
    }

    protected function trashRoute(): string
    {
        return 'person-types.trash.index';
    }
}
