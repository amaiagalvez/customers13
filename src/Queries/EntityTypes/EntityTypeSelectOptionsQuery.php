<?php

namespace Entities13\Queries\EntityTypes;

use Entities13\Models\EntityType;
use Illuminate\Support\Collection;
use Basics13\Queries\ListQueryBase;
use Illuminate\Database\Eloquent\Builder;
use Entities13\Contracts\EntityIntegration;

/**
 * @extends ListQueryBase<EntityType>
 */
final class EntityTypeSelectOptionsQuery extends ListQueryBase
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
     * Options for the entity form select. Euskera is the text every option shows, while the term
     * the user types is matched against all four languages, so a type can be found by any of the
     * names it has.
     *
     * @return Collection<int, array{id: int, text: string}>
     */
    public function search(string $search): Collection
    {
        $query = EntityType::query()
            ->where('active', true)
            ->orderBy('ENT_entity_types.name_eu')
            ->orderBy('ENT_entity_types.id');

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
            ->map(fn (EntityType $entityType): array => [
                'id' => $entityType->id,
                'text' => $entityType->name_eu,
            ]);
    }

    /**
     * @return class-string<EntityType>
     */
    protected function getModelClass(): string
    {
        return EntityType::class;
    }

    /**
     * The selector joins nothing, so the integration contributes no columns to it.
     */
    protected function integration(): EntityIntegration
    {
        return $this->integration;
    }
}
