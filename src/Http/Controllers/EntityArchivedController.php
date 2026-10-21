<?php

namespace Entities13\Http\Controllers;

use Illuminate\View\View;
use Entities13\Models\Entity;
use Illuminate\Http\RedirectResponse;
use Entities13\Http\Requests\EntityListRequest;
use Entities13\Queries\Entities\EntityListQuery;
use Basics13\Http\Controllers\ArchivedController;
use Entities13\Transformers\EntityListTransformer;

class EntityArchivedController extends ArchivedController
{
    public function index(
        EntityListRequest $request,
        EntityListQuery $query,
        EntityListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $filters = $request->filters() ?? [];
        $entities = $query->archived($search, $filters);

        return $this->listView($request, 'entities13::entities.list', [
            'entities' => $entities,
            'list' => $transformer->archived(
                $entities,
                $search,
                $query->stateCounts(archivedTotal: $search === '' && $filters === [] ? $entities->total() : null),
            ),
            'searchableFields' => $query->searchableFields('archived'),
            'filters' => $filters,
            'scope' => 'archived',
        ]);
    }

    public function archive(Entity $entity): RedirectResponse
    {
        return $this->archiveRecord($entity);
    }

    public function activate(Entity $entity): RedirectResponse
    {
        return $this->activateRecord($entity);
    }

    protected function activeRoute(): string
    {
        return 'entities.index';
    }

    protected function archivedRoute(): string
    {
        return 'entities.archived.index';
    }
}
