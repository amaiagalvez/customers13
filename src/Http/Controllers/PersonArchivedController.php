<?php

namespace Entities13\Http\Controllers;

use Illuminate\View\View;
use Entities13\Models\Person;
use Illuminate\Http\RedirectResponse;
use Entities13\Http\Requests\PersonListRequest;
use Entities13\Queries\Persons\PersonListQuery;
use Basics13\Http\Controllers\ArchivedController;
use Entities13\Transformers\PersonListTransformer;

class PersonArchivedController extends ArchivedController
{
    public function index(
        PersonListRequest $request,
        PersonListQuery $query,
        PersonListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $filters = $request->filters() ?? [];
        $persons = $query->archived($search, $filters);

        return $this->listView($request, 'entities13::persons.list', [
            'persons' => $persons,
            'list' => $transformer->archived(
                $persons,
                $search,
                $query->stateCounts(archivedTotal: $search === '' && $filters === [] ? $persons->total() : null),
            ),
            'searchableFields' => $query->searchableFields('archived'),
            'filters' => $filters,
            'scope' => 'archived',
        ]);
    }

    public function archive(Person $person): RedirectResponse
    {
        return $this->archiveRecord($person);
    }

    public function activate(Person $person): RedirectResponse
    {
        return $this->activateRecord($person);
    }

    protected function activeRoute(): string
    {
        return 'persons.index';
    }

    protected function archivedRoute(): string
    {
        return 'persons.archived.index';
    }
}
