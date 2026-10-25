<?php

namespace Entities13\Queries\Persons;

use Entities13\Models\Entity;
use Entities13\Models\Person;
use Entities13\Models\PersonType;
use Basics13\Queries\ListQueryBase;
use Illuminate\Database\Eloquent\Builder;
use Entities13\Contracts\EntityIntegration;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @extends ListQueryBase<Person>
 *
 * @phpstan-import-type SearchFilters from ListQueryBase
 * @phpstan-import-type SearchFields from ListQueryBase
 */
final class PersonListQuery extends ListQueryBase
{
    /**
     * Search columns with table prefix for the query builder.
     *
     * @var list<string>
     */
    private const SEARCH_COLUMNS = [
        'ENT_persons.name',
        'ENT_persons.last_name_1',
        'ENT_persons.last_name_2',
        'ENT_persons.document_number',
    ];

    private const SCOPE_ACTIVE = 'active';

    private const SCOPE_ARCHIVED = 'archived';

    private const SCOPE_TRASHED = 'trashed';

    public function __construct(private readonly EntityIntegration $integration) {}

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, Person>
     */
    public function active(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->baseQuery(self::SCOPE_ACTIVE)
                ->with(['entity', 'personTypes']),
            $search,
            searchColumns: $this->searchColumnsFor(self::SCOPE_ACTIVE),
            filters: $filters,
            scope: self::SCOPE_ACTIVE,
        );
    }

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, Person>
     */
    public function archived(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->baseQuery(self::SCOPE_ARCHIVED)
                ->with(['entity', 'personTypes']),
            $search,
            searchColumns: $this->searchColumnsFor(self::SCOPE_ARCHIVED),
            filters: $filters,
            scope: self::SCOPE_ARCHIVED,
        );
    }

    /**
     * @param  SearchFilters  $filters
     * @return LengthAwarePaginator<int, Person>
     */
    public function trashed(string $search, array $filters = []): LengthAwarePaginator
    {
        return $this->paginate(
            $this->baseQuery(self::SCOPE_TRASHED)
                ->with(['entity', 'personTypes']),
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
                'column' => 'ENT_persons.name',
                'label' => 'Person name',
                'type' => 'text',
                'visible' => true,
                'order' => 10,
                'group' => 'basic',
            ],
            'last_name_1' => [
                'column' => 'ENT_persons.last_name_1',
                'label' => 'First last name',
                'type' => 'text',
                'visible' => true,
                'order' => 20,
                'group' => 'basic',
            ],
            'last_name_2' => [
                'column' => 'ENT_persons.last_name_2',
                'label' => 'Second last name',
                'type' => 'text',
                'visible' => true,
                'order' => 30,
                'group' => 'basic',
            ],
            'document_number' => [
                'column' => 'ENT_persons.document_number',
                'label' => 'Document number',
                'type' => 'text',
                'visible' => true,
                'order' => 40,
                'group' => 'basic',
            ],
            'lang' => [
                'column' => 'ENT_persons.lang',
                'label' => 'Language',
                'type' => 'select',
                'visible' => true,
                'order' => 50,
                'group' => 'basic',
                'options' => [
                    'eu' => 'Euskera',
                    'es' => 'Español',
                    'fr' => 'Français',
                    'en' => 'English',
                ],
                'multiple' => false,
            ],
            // Only the trash mixes active and archived records: on the active and archived lists
            // the status filter could only return everything or nothing.
            ...($scope === 'trashed' ? ['active' => [
                'column' => 'ENT_persons.active',
                'label' => 'Person status',
                'type' => 'boolean',
                'visible' => true,
                'order' => 10,
                'group' => 'status',
            ]] : []),
            'created_at' => [
                'column' => 'ENT_persons.created_at',
                'label' => 'Person creation date',
                'type' => 'date',
                'visible' => true,
                'order' => 10,
                'group' => 'dates',
            ],
            'entity_id' => [
                'column' => 'ENT_entities.id',
                'relation' => 'entity',
                'label' => 'Entity',
                'type' => 'select',
                'visible' => true,
                'order' => 10,
                'group' => 'relations',
                'options' => fn (string $scope) => Entity::query()
                    ->where('active', $scope === 'active')
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all(),
                'multiple' => false,
            ],
            'person_type_ids' => [
                'column' => 'ENT_person_types.id',
                'relation' => 'personTypes',
                'label' => 'Person types',
                'help' => 'Equals shows the persons holding at least one of the selected types; not equals, those holding none of them.',
                'type' => 'select',
                'visible' => true,
                'order' => 20,
                'group' => 'relations',
                'options' => fn (string $scope) => PersonType::query()
                    ->where('active', $scope === 'active')
                    ->orderBy('name_eu')
                    ->pluck('name_eu', 'id')
                    ->all(),
                'multiple' => true,
            ],
        ];
    }

    /**
     * @return class-string<Person>
     */
    protected function getModelClass(): string
    {
        return Person::class;
    }

    protected function integration(): EntityIntegration
    {
        return $this->integration;
    }

    /**
     * Build the base query for a list scope: joins, status and ordering, without the relations the
     * listing displays. The export does not render them.
     *
     * @return Builder<Person>
     */
    private function baseQuery(string $scope): Builder
    {
        return match ($scope) {
            self::SCOPE_ARCHIVED => Person::query()
                ->where('ENT_persons.active', false)
                ->orderBy('ENT_persons.name')
                ->orderBy('ENT_persons.last_name_1')
                ->orderBy('ENT_persons.last_name_2')
                ->orderBy('ENT_persons.id'),
            self::SCOPE_TRASHED => Person::onlyTrashed()
                ->latest('ENT_persons.deleted_at')
                ->orderBy('ENT_persons.id'),
            default => Person::query()
                ->where('ENT_persons.active', true)
                ->orderBy('ENT_persons.name')
                ->orderBy('ENT_persons.last_name_1')
                ->orderBy('ENT_persons.last_name_2')
                ->orderBy('ENT_persons.id'),
        };
    }

    /**
     * Search columns for a list scope.
     *
     * @return non-empty-list<literal-string>
     */
    private function searchColumnsFor(string $scope): array
    {
        return match ($scope) {
            self::SCOPE_ARCHIVED => [...self::SEARCH_COLUMNS, 'ENT_persons.updated_at'],
            self::SCOPE_TRASHED => [...self::SEARCH_COLUMNS, 'ENT_persons.deleted_at'],
            default => self::SEARCH_COLUMNS,
        };
    }

    /**
     * Apply the person type filter using whereHas (equals/not_equals semantics).
     * Overrides the base class to use the relation name 'personTypes' and column 'ENT_person_types.id'.
     *
     * @param  Builder<Person>  $query
     */
    protected function applyRelationFilter(Builder $query, string $relation, string $column, string $operator, mixed $value): void
    {
        if (! in_array($relation, ['personTypes'], true)) {
            // Delegate to parent for other relations (e.g., entity)
            parent::applyRelationFilter($query, $relation, $column, $operator, $value);

            return;
        }

        $values = array_values(array_filter(is_array($value) ? $value : [$value], fn ($v) => $v !== null && $v !== ''));

        if ($values === []) {
            return;
        }

        $constraint = fn (Builder $q) => $q->whereIn('ENT_person_types.id', $values);

        if ($operator === 'not_equals') {
            $query->whereDoesntHave('personTypes', $constraint);
        } else {
            $query->whereHas('personTypes', $constraint);
        }
    }

    /**
     * The list query without pagination, for the Excel export: the same scope, search and filters
     * the page is showing.
     *
     * @param  SearchFilters  $filters
     * @return Builder<Person>
     */
    public function filteredQuery(string $search, array $filters = [], string $scope = self::SCOPE_ACTIVE): Builder
    {
        return $this->filtered($this->baseQuery($scope)->with(['entity', 'personTypes']), $search, $this->searchColumnsFor($scope), $filters, $scope);
    }
}
