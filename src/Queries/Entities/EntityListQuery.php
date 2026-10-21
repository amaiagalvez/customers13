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
            $this->scoped(self::SCOPE_ACTIVE, Entity::query()->where('ENT_entities.active', true)->with('entityTypes')->orderBy('ENT_entities.name')->orderBy('ENT_entities.id')),
            $search,
            /** @phpstan-ignore argument.type */
            searchColumns: $this->searchColumns(self::SCOPE_ACTIVE, ['ENT_entities.name']),
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
            $this->scoped(self::SCOPE_ARCHIVED, Entity::query()->where('ENT_entities.active', false)->with('entityTypes')->orderBy('ENT_entities.name')->orderBy('ENT_entities.id')),
            $search,
            /** @phpstan-ignore argument.type */
            searchColumns: $this->searchColumns(self::SCOPE_ARCHIVED, ['ENT_entities.name', 'ENT_entities.updated_at']),
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
            $this->scoped(self::SCOPE_TRASHED, Entity::onlyTrashed()->with('entityTypes')->latest('deleted_at')->orderBy('id')),
            $search,
            /** @phpstan-ignore argument.type */
            searchColumns: $this->searchColumns(self::SCOPE_TRASHED, ['ENT_entities.name', 'ENT_entities.deleted_at']),
            filters: $filters,
            scope: self::SCOPE_TRASHED,
        );
    }

    /**
     * Get the searchable field definitions for the current scope.
     *
     * @param  string  $scope  One of 'active', 'archived', 'trashed'
     * @return array<string, array{column: string, label: string, type: string, operators?: array<int,string>, default_operator?: string, visible?: bool, order?: int, group?: string, nullable?: bool, placeholder?: string, help?: string, condition?: callable, options?: array<int,string>|callable, multiple?: bool}>
     */
    public function searchableFields(string $scope): array
    {
        return [
            'name' => [
                'column' => 'ENT_entities.name',
                'label' => 'entities13::messages.name',
                'type' => 'text',
                'visible' => true,
                'order' => 10,
                'group' => 'basic',
            ],
            'notes' => [
                'column' => 'ENT_entities.notes',
                'label' => 'entities13::messages.notes',
                'type' => 'text',
                'operators' => ['contains', 'equals', 'not_equals'], // Excludes starts_with/ends_with
                'visible' => false,
                'order' => 20,
                'group' => 'basic',
            ],
            'active' => [
                'column' => 'ENT_entities.active',
                'label' => 'entities13::messages.active',
                'type' => 'boolean',
                'visible' => true,
                'order' => 10,
                'group' => 'status',
            ],
            'created_at' => [
                'column' => 'ENT_entities.created_at',
                'label' => 'entities13::messages.created_at',
                'type' => 'date',
                'visible' => true,
                'order' => 10,
                'group' => 'dates',
            ],
            'entity_type_ids' => [
                'column' => 'ENT_entity_entity_type.entity_type_id',
                'label' => 'entities13::messages.entity_types',
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
        $this->integration->scope($query, $scope, 'entity_projects');

        return $query;
    }
}
