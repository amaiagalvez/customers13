<?php

namespace Entities13\Queries\Entities;

use Entities13\Models\Entity;
use Entities13\Models\EntityType;
use Basics13\Queries\ListQueryBase;
use Illuminate\Database\Eloquent\Builder;
use Entities13\Contracts\EntityIntegration;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @extends ListQueryBase<Entity>
 *
 * @phpstan-import-type SearchFilters from ListQueryBase
 * @phpstan-import-type SearchFields from ListQueryBase
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
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, Entity>
     */
    public function active(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->baseQuery(self::SCOPE_ACTIVE)->with('entityTypes'),
            $search,
            searchColumns: $this->searchColumnsFor(self::SCOPE_ACTIVE),
            filters: $filters,
            scope: self::SCOPE_ACTIVE,
        );
    }

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, Entity>
     */
    public function archived(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->baseQuery(self::SCOPE_ARCHIVED)->with('entityTypes'),
            $search,
            searchColumns: $this->searchColumnsFor(self::SCOPE_ARCHIVED),
            filters: $filters,
            scope: self::SCOPE_ARCHIVED,
        );
    }

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, Entity>
     */
    public function trashed(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->baseQuery(self::SCOPE_TRASHED)->with('entityTypes'),
            $search,
            searchColumns: $this->searchColumnsFor(self::SCOPE_TRASHED),
            filters: $filters,
            scope: self::SCOPE_TRASHED,
        );
    }

    /**
     * Get the searchable field definitions for the current scope.
     *
     * @param  string  $scope  One of 'active', 'archived', 'trashed'
     * @return SearchFields
     */
    public function searchableFields(string $scope): array
    {
        return [
            'name' => [
                'column' => 'ENT_entities.name',
                'label' => 'Entity name',
                'type' => 'text',
                'visible' => true,
                'order' => 10,
                'group' => 'basic',
            ],
            'notes' => [
                'column' => 'ENT_entities.notes',
                'label' => 'Entity notes',
                'type' => 'text',
                'operators' => ['contains', 'equals', 'not_equals'], // Excludes starts_with/ends_with
                'visible' => false,
                'order' => 20,
                'group' => 'basic',
            ],
            // Only the trash mixes active and archived records: on the active and archived lists
            // the status filter could only return everything or nothing.
            ...($scope === 'trashed' ? ['active' => [
                'column' => 'ENT_entities.active',
                'label' => 'Entity status',
                'type' => 'boolean',
                'visible' => true,
                'order' => 10,
                'group' => 'status',
            ]] : []),
            'created_at' => [
                'column' => 'ENT_entities.created_at',
                'label' => 'Entity creation date',
                'type' => 'date',
                'visible' => true,
                'order' => 10,
                'group' => 'dates',
            ],
            'entity_type_ids' => [
                'column' => 'ENT_entity_types.id',
                'relation' => 'entityTypes',
                'label' => 'Entity types',
                'help' => 'Equals shows the entities holding at least one of the selected types; not equals, those holding none of them.',
                'type' => 'select',
                'visible' => true,
                'order' => 10,
                'group' => 'relations',
                'options' => fn (string $scope) => EntityType::query()
                    ->where('active', $scope === 'active')
                    ->orderBy('name_eu')
                    ->pluck('name_eu', 'id')
                    ->all(),
                'multiple' => true,
            ],
        ];
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
        // A neutral alias: this package cannot know what an integration joins.
        $this->integration->scope($query, $scope, 'entity_related');

        return $query;
    }

    /**
     * Build the base query for a list scope: joins, status and ordering, without the relations the
     * listing displays. The export does not render them.
     *
     * @return Builder<Entity>
     */
    private function baseQuery(string $scope): Builder
    {
        return match ($scope) {
            self::SCOPE_ARCHIVED => $this->scoped(self::SCOPE_ARCHIVED, Entity::query()->where('ENT_entities.active', false)->orderBy('ENT_entities.name')->orderBy('ENT_entities.id')),
            self::SCOPE_TRASHED => $this->scoped(self::SCOPE_TRASHED, Entity::onlyTrashed()->latest('ENT_entities.deleted_at')->orderBy('ENT_entities.id')),
            default => $this->scoped(self::SCOPE_ACTIVE, Entity::query()->where('ENT_entities.active', true)->orderBy('ENT_entities.name')->orderBy('ENT_entities.id')),
        };
    }

    /**
     * Search columns for a list scope, with whatever the integration contributes appended.
     *
     * @return non-empty-list<literal-string>
     */
    private function searchColumnsFor(string $scope): array
    {
        return $this->searchColumns($scope, match ($scope) {
            self::SCOPE_ARCHIVED => ['ENT_entities.name', 'ENT_entities.updated_at'],
            self::SCOPE_TRASHED => ['ENT_entities.name', 'ENT_entities.deleted_at'],
            default => ['ENT_entities.name'],
        });
    }

    /**
     * The list query without pagination, for the Excel export: the same scope, search and filters
     * the page is showing.
     *
     * @param  SearchFilters  $filters
     * @return Builder<Entity>
     */
    public function filteredQuery(string $search, array $filters = [], string $scope = self::SCOPE_ACTIVE): Builder
    {
        return $this->filtered($this->baseQuery($scope), $search, $this->searchColumnsFor($scope), $filters, $scope);
    }
}
