<?php

namespace Entities13\Http\Controllers;

use Illuminate\View\View;
use Entities13\Models\EntityType;
use Illuminate\Http\RedirectResponse;
use Basics13\Http\Controllers\ArchivedController;
use Entities13\Http\Requests\EntityTypeListRequest;
use Entities13\Transformers\EntityTypeListTransformer;
use Entities13\Queries\EntityTypes\EntityTypeListQuery;

class EntityTypeArchivedController extends ArchivedController
{
    public function index(
        EntityTypeListRequest $request,
        EntityTypeListQuery $query,
        EntityTypeListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $filters = $request->filters() ?? [];
        $entityTypes = $query->archived($search, $filters);

        return $this->listView($request, 'entities13::entity-types.list', [
            'entityTypes' => $entityTypes,
            'list' => $transformer->archived(
                $entityTypes,
                $search,
                $query->stateCounts(archivedTotal: $search === '' ? $entityTypes->total() : null),
            ),
            'searchableFields' => $query->searchableFields('archived'),
            'filters' => $filters,
            'scope' => 'archived',
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
