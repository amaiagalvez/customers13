<?php

namespace Entities13\Http\Controllers;

use Illuminate\View\View;
use Entities13\Models\PersonType;
use Illuminate\Http\RedirectResponse;
use Basics13\Http\Controllers\ArchivedController;
use Entities13\Http\Requests\PersonTypeListRequest;
use Entities13\Transformers\PersonTypeListTransformer;
use Entities13\Queries\PersonTypes\PersonTypeListQuery;

class PersonTypeArchivedController extends ArchivedController
{
    public function index(
        PersonTypeListRequest $request,
        PersonTypeListQuery $query,
        PersonTypeListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $personTypes = $query->archived($search);

        return $this->listView($request, 'entities13::person-types.list', [
            'personTypes' => $personTypes,
            'list' => $transformer->archived(
                $personTypes,
                $search,
                $query->stateCounts(archivedTotal: $search === '' ? $personTypes->total() : null),
            ),
            'scope' => 'archived',
        ]);
    }

    public function archive(PersonType $personType): RedirectResponse
    {
        return $this->archiveRecord($personType);
    }

    public function activate(PersonType $personType): RedirectResponse
    {
        return $this->activateRecord($personType);
    }

    protected function activeRoute(): string
    {
        return 'person-types.index';
    }

    protected function archivedRoute(): string
    {
        return 'person-types.archived.index';
    }
}
