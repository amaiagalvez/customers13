<?php

namespace Entities13\Queries\PersonTypes;

use Entities13\Models\PersonType;
use Illuminate\Support\Collection;
use Basics13\Queries\ListQueryBase;
use Illuminate\Database\Eloquent\Builder;
use Entities13\Contracts\EntityIntegration;

/**
 * @extends ListQueryBase<PersonType>
 */
final class PersonTypeSelectOptionsQuery extends ListQueryBase
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
     * Options for the person type form select. Euskera is the text every option shows, while the term
     * the user types is matched against all four languages, so a type can be found by any of the
     * names it has.
     *
     * @return Collection<int, array{id: int, text: string}>
     */
    public function search(string $search): Collection
    {
        $query = PersonType::query()
            ->where('active', true)
            ->orderBy('ENT_person_types.name_eu')
            ->orderBy('ENT_person_types.id');

        if ($search !== '') {
            $pattern = $this->searchPattern($search);

            $query->where(function (Builder $query) use ($pattern): void {
                foreach (self::NAME_COLUMNS as $column) {
                    $query->orWhereRaw($column.' LIKE ? ESCAPE \''.self::LIKE_ESCAPE.'\'', [$pattern]);
                }
            });
        }

        return $query->limit(self::PER_PAGE)
            ->get(['id', 'name_eu'])
            ->map(fn (PersonType $personType): array => [
                'id' => $personType->id,
                'text' => $personType->name_eu,
            ]);
    }

    /**
     * @return class-string<PersonType>
     */
    protected function getModelClass(): string
    {
        return PersonType::class;
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
