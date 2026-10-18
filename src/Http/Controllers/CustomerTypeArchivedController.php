<?php

namespace entities13\Http\Controllers;

use Illuminate\View\View;
use entities13\Models\EntityType;
use Illuminate\Http\RedirectResponse;
use Basics13\Http\Controllers\ArchivedController;
use entities13\Http\Requests\EntityTypeListRequest;
use entities13\Transformers\EntityTypeListTransformer;
use entities13\Queries\EntityTypes\EntityTypeListQuery;

class EntityTypeArchivedController extends ArchivedController
{
    public function index(
        EntityTypeListRequest $request,
        EntityTypeListQuery $query,
        EntityTypeListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $entityTypes = $query->archived($search);

        return $this->listView($request, 'entities13::entity-types.list', [
            'entityTypes' => $entityTypes,
            'list' => $transformer->archived(
                $entityTypes,
                $search,
                $query->stateCounts(archivedTotal: $search === '' ? $entityTypes->total() : null),
            ),
        ]);
    }

    public function archive(EntityType $entityType): RedirectResponse
    {
        return $this->archiveRecord($entityType);
    }

    public function activate(EntityType $entityType): RedirectResponse
    {
        return $this->activateRecord($entityType);
    }

    protected function activeRoute(): string
    {
        return 'entity-types.index';
    }

    protected function archivedRoute(): string
    {
        return 'entity-types.archived.index';
    }
}
