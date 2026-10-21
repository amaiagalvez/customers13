<?php

namespace Entities13\Queries\EntityTypes;

use Entities13\Models\EntityType;
use Basics13\Queries\ListQueryBase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Entities13\Contracts\EntityIntegration;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * @extends ListQueryBase<EntityType>
 *
 * @phpstan-import-type SearchFilters from ListQueryBase
 */
final class EntityTypeListQuery extends ListQueryBase
{
    /**
     * Search columns with table prefix for the query builder.
     *
     * @var list<string>
     */
    private const NAME_COLUMNS = [
        'ENT_entity_types.name_eu',
        'ENT_entity_types.name_es',
        'ENT_entity_types.name_fr',
        'ENT_entity_types.name_en',
    ];

    public function __construct(private readonly EntityIntegration $integration) {}

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, EntityType>
     */
    public function active(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->withEntitiesCount(EntityType::query()
                ->where('ENT_entity_types.active', true)
                ->orderBy('ENT_entity_types.name_eu')
                ->orderBy('ENT_entity_types.id')),
            $search,
            searchColumns: self::NAME_COLUMNS,
            filters: $filters,
            scope: 'active',
        );
    }

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, EntityType>
     */
    public function archived(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->withEntitiesCount(EntityType::query()
                ->where('ENT_entity_types.active', false)
                ->orderBy('ENT_entity_types.name_eu')
                ->orderBy('ENT_entity_types.id')),
            $search,
            searchColumns: [...self::NAME_COLUMNS, 'ENT_entity_types.updated_at'],
            filters: $filters,
            scope: 'archived',
        );
    }

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, EntityType>
     */
    public function trashed(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->withEntitiesCount(EntityType::onlyTrashed()
                ->latest('deleted_at')
                ->orderBy('id')),
            $search,
            searchColumns: [...self::NAME_COLUMNS, 'ENT_entity_types.deleted_at'],
            filters: $filters,
            scope: 'trashed',
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
            'name_eu' => [
                'column' => 'ENT_entity_types.name_eu',
                'label' => 'entities13::messages.name_eu',
                'type' => 'text',
                'visible' => true,
                'order' => 10,
                'group' => 'names',
            ],
            'name_es' => [
                'column' => 'ENT_entity_types.name_es',
                'label' => 'entities13::messages.name_es',
                'type' => 'text',
                'visible' => true,
                'order' => 20,
                'group' => 'names',
            ],
            'name_fr' => [
                'column' => 'ENT_entity_types.name_fr',
                'label' => 'entities13::messages.name_fr',
                'type' => 'text',
                'visible' => true,
                'order' => 30,
                'group' => 'names',
            ],
            'name_en' => [
                'column' => 'ENT_entity_types.name_en',
                'label' => 'entities13::messages.name_en',
                'type' => 'text',
                'visible' => true,
                'order' => 40,
                'group' => 'names',
            ],
            'notes' => [
                'column' => 'ENT_entity_types.notes',
                'label' => 'entities13::messages.notes',
                'type' => 'text',
                'operators' => ['contains', 'equals', 'not_equals'], // Excludes starts_with/ends_with
                'visible' => false,
                'order' => 50,
                'group' => 'basic',
            ],
            'active' => [
                'column' => 'ENT_entity_types.active',
                'label' => 'entities13::messages.active',
                'type' => 'boolean',
                'visible' => true,
                'order' => 10,
                'group' => 'status',
            ],
            'created_at' => [
                'column' => 'ENT_entity_types.created_at',
                'label' => 'entities13::messages.created_at',
                'type' => 'date',
                'visible' => true,
                'order' => 10,
                'group' => 'dates',
            ],
        ];
    }

    /**
     * @return class-string<EntityType>
     */
    protected function getModelClass(): string
    {
        return EntityType::class;
    }

    /**
     * The type list joins nothing, so the integration contributes no columns to it. The accessor is
     * implemented because the base declares it, and kept injected so every list query of this
     * package takes its integration the same way.
     */
    protected function integration(): EntityIntegration
    {
        return $this->integration;
    }

    /**
     * A trashed type is found by its euskera name, which is the one the create form names first and
     * the only name a type is required to have.
     *
     * @param  Builder<EntityType>  $query
     */
    protected function firstTrashedByName(Builder $query, string $name): ?Model
    {
        return $query->where('name_eu', $name)->latest('deleted_at')->first();
    }

    /**
     * How many entities hold the type, deleted ones included: a type in the trash keeps the pivot
     * rows of the entities that held it, and that count is what the list needs to know before it
     * offers to delete a type instead of archiving it.
     *
     * @param  Builder<EntityType>  $query
     * @return Builder<EntityType>
     */
    private function withEntitiesCount(Builder $query): Builder
    {
        return $query->withCount([
            'entities' => fn (Builder $entities): Builder => $entities
                ->withoutGlobalScope(SoftDeletingScope::class),
        ]);
    }
}
