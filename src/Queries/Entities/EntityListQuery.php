<?php

namespace Entities13\Queries\Entities;

use Entities13\Models\Entity;
use Basics13\Queries\ListQueryBase;
use Illuminate\Database\Eloquent\Builder;
use Entities13\Contracts\EntityIntegration;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @extends ListQueryBase<Entity>
 */
final class EntityListQuery extends ListQueryBase
{
    /**
     * The list the columns below belong to. Passed to the integration so one binding can tell an
     * active row from an archived or trashed one, which is all it needs to count the right slice.
     */
    private const SCOPE_ACTIVE = 'active';

    private const SCOPE_ARCHIVED = 'archived';

    private const SCOPE_TRASHED = 'trashed';

    public function __construct(private readonly EntityIntegration $integration) {}

    /**
     * @return LengthAwarePaginator<int, Entity>
     */
    public function active(string $search): LengthAwarePaginator
    {
        // Unique names among non-deleted entities keep the ordering total on its own; the id
        // is here so this list breaks ties the same way the archived and trash lists do.
        return $this->paginate(
            $this->scoped(self::SCOPE_ACTIVE, Entity::query()->where('ENT_entities.active', true)->with('entityTypes')->orderBy('ENT_entities.name')->orderBy('ENT_entities.id')),
            $search,
            /** @phpstan-ignore argument.type */
            searchColumns: $this->searchColumns(self::SCOPE_ACTIVE, ['ENT_entities.name']),
        );
    }

    /**
     * @return LengthAwarePaginator<int, Entity>
     */
    public function archived(string $search): LengthAwarePaginator
    {
        return $this->paginate(
            $this->scoped(self::SCOPE_ARCHIVED, Entity::query()->where('ENT_entities.active', false)->with('entityTypes')->orderBy('ENT_entities.name')->orderBy('ENT_entities.id')),
            $search,
            /** @phpstan-ignore argument.type */
            searchColumns: $this->searchColumns(self::SCOPE_ARCHIVED, ['ENT_entities.name', 'ENT_entities.updated_at']),
        );
    }

    /**
     * @return LengthAwarePaginator<int, Entity>
     */
    public function trashed(string $search): LengthAwarePaginator
    {
        return $this->paginate(
            $this->scoped(self::SCOPE_TRASHED, Entity::onlyTrashed()->with('entityTypes')->latest('deleted_at')->orderBy('id')),
            $search,
            /** @phpstan-ignore argument.type */
            searchColumns: $this->searchColumns(self::SCOPE_TRASHED, ['ENT_entities.name', 'ENT_entities.deleted_at']),
        );
    }

    /**
     * @return class-string<Entity>
     */
    protected function getModelClass(): string
    {
        return Entity::class;
    }

    protected function integration(): EntityIntegration
    {
        return $this->integration;
    }

    /**
     * Hand the query to the integration before anything searches or orders it, so the columns it
     * contributes to the search and the ordering are already there.
     *
     * @param  Builder<Entity>  $query
     * @return Builder<Entity>
     */
    private function scoped(string $scope, Builder $query): Builder
    {
        $this->integration->scope($query, $scope, 'entity_projects');

        return $query;
    }
}
