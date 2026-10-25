<?php

namespace Entities13\Queries\PersonTypes;

use Entities13\Models\PersonType;
use Basics13\Queries\ListQueryBase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Entities13\Contracts\EntityIntegration;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * @extends ListQueryBase<PersonType>
 *
 * @phpstan-import-type SearchFilters from ListQueryBase
 */
final class PersonTypeListQuery extends ListQueryBase
{
    /**
     * Search columns with table prefix for the query builder.
     *
     * @var list<string>
     */
    private const NAME_COLUMNS = [
        'ENT_person_types.name_eu',
        'ENT_person_types.name_es',
        'ENT_person_types.name_fr',
        'ENT_person_types.name_en',
    ];

    public function __construct(private readonly EntityIntegration $integration) {}

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, PersonType>
     */
    public function active(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->withPersonsCount(PersonType::query()
                ->where('ENT_person_types.active', true)
                ->orderBy('ENT_person_types.name_eu')
                ->orderBy('ENT_person_types.id')),
            $search,
            searchColumns: self::NAME_COLUMNS,
            filters: $filters,
            scope: 'active',
        );
    }

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, PersonType>
     */
    public function archived(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->withPersonsCount(PersonType::query()
                ->where('ENT_person_types.active', false)
                ->orderBy('ENT_person_types.name_eu')
                ->orderBy('ENT_person_types.id')),
            $search,
            searchColumns: [...self::NAME_COLUMNS, 'ENT_person_types.updated_at'],
            filters: $filters,
            scope: 'archived',
        );
    }

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, PersonType>
     */
    public function trashed(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->withPersonsCount(PersonType::onlyTrashed()
                ->latest('deleted_at')
                ->orderBy('id')),
            $search,
            searchColumns: [...self::NAME_COLUMNS, 'ENT_person_types.deleted_at'],
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
        // Person types are a short catalogue: the list search is enough, no advanced filters.
        return [];
    }

    /**
     * @return class-string<PersonType>
     */
    protected function getModelClass(): string
    {
        return PersonType::class;
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
     * @param  Builder<PersonType>  $query
     */
    protected function firstTrashedByName(Builder $query, string $name): ?Model
    {
        return $query->where('name_eu', $name)->latest('deleted_at')->first();
    }

    /**
     * How many persons hold the type, deleted ones included: a type in the trash keeps the pivot
     * rows of the persons that held it, and that count is what the list needs to know before it
     * offers to delete a type instead of archiving it.
     *
     * @param  Builder<PersonType>  $query
     * @return Builder<PersonType>
     */
    private function withPersonsCount(Builder $query): Builder
    {
        return $query->withCount([
            'persons' => fn (Builder $persons): Builder => $persons
                ->withoutGlobalScope(SoftDeletingScope::class),
        ]);
    }
}
