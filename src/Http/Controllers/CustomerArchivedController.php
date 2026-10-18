<?php

namespace entities13\Http\Controllers;

use Illuminate\View\View;
use entities13\Models\Entity;
use Illuminate\Http\RedirectResponse;
use Basics13\Http\Controllers\ArchivedController;
use entities13\Http\Requests\EntityListRequest;
use entities13\Queries\Entities\EntityListQuery;
use entities13\Transformers\EntityListTransformer;

class EntityArchivedController extends ArchivedController
{
    public function index(
        EntityListRequest $request,
        EntityListQuery $query,
        EntityListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $entities = $query->archived($search);

        return $this->listView($request, 'entities13::entities.list', [
            'entities' => $entities,
            'list' => $transformer->archived(
                $entities,
                $search,
                $query->stateCounts(archivedTotal: $search === '' ? $entities->total() : null),
            ),
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
