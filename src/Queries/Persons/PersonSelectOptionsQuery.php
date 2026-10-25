<?php

namespace Entities13\Queries\Persons;

use Entities13\Models\Person;
use Illuminate\Support\Collection;
use Basics13\Queries\ListQueryBase;
use Illuminate\Database\Eloquent\Builder;
use Entities13\Contracts\EntityIntegration;

/**
 * @extends ListQueryBase<Person>
 */
final class PersonSelectOptionsQuery extends ListQueryBase
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

    public function __construct(private readonly EntityIntegration $integration) {}

    /**
     * Options for the person form select. Full name is the text every option shows, while the term
     * the user types is matched against name, last names and document number.
     *
     * @return Collection<int, array{id: int, text: string}>
     */
    public function search(string $search): Collection
    {
        $query = Person::query()
            ->where('active', true)
            ->orderBy('ENT_persons.name')
            ->orderBy('ENT_persons.last_name_1')
            ->orderBy('ENT_persons.last_name_2')
            ->orderBy('ENT_persons.id');

        if ($search !== '') {
            $pattern = $this->searchPattern($search);

            $query->where(function (Builder $query) use ($pattern): void {
                foreach (self::SEARCH_COLUMNS as $column) {
                    $query->orWhereRaw($column.' LIKE ? ESCAPE \''.self::LIKE_ESCAPE.'\'', [$pattern]);
                }
            });
        }

        return $query->limit(self::PER_PAGE)
            ->get(['id', 'name', 'last_name_1', 'last_name_2'])
            ->map(fn (Person $person): array => [
                'id' => $person->id,
                'text' => $person->getFullName(),
            ]);
    }

    /**
     * @return class-string<Person>
     */
    protected function getModelClass(): string
    {
        return Person::class;
    }

    /**
     * The selector joins nothing, so the integration contributes no columns to it.
     */
    protected function integration(): EntityIntegration
    {
        return $this->integration;
    }

    /**
     * Select-option queries do not expose list filters.
     *
     * @return array{}
     */
    public function searchableFields(string $scope): array
    {
        return [];
    }
}
