<?php

namespace Customers13\Queries\CustomerTypes;

use Illuminate\Support\Collection;
use Basics13\Queries\ListQueryBase;
use Customers13\Models\CustomerType;
use Illuminate\Database\Eloquent\Builder;
use Customers13\Contracts\CustomerIntegration;

/**
 * @extends ListQueryBase<CustomerType>
 */
final class CustomerTypeSelectOptionsQuery extends ListQueryBase
{
    /**
     * Search columns with table prefix for the query builder.
     *
     * @var list<string>
     */
    private const NAME_COLUMNS = [
        'CUM_customer_types.name_eu',
        'CUM_customer_types.name_es',
        'CUM_customer_types.name_fr',
        'CUM_customer_types.name_en',
    ];

    public function __construct(private readonly CustomerIntegration $integration) {}

    /**
     * Options for the customer form select. Euskera is the text every option shows, while the term
     * the user types is matched against all four languages, so a type can be found by any of the
     * names it has.
     *
     * @return Collection<int, array{id: int, text: string}>
     */
    public function search(string $search): Collection
    {
        $query = CustomerType::query()
            ->where('active', true)
            ->orderBy('CUM_customer_types.name_eu')
            ->orderBy('CUM_customer_types.id');

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
            ->map(fn (CustomerType $customerType): array => [
                'id' => $customerType->id,
                'text' => $customerType->name_eu,
            ]);
    }

    /**
     * @return class-string<CustomerType>
     */
    protected function getModelClass(): string
    {
        return CustomerType::class;
    }

    /**
     * The selector joins nothing, so the integration contributes no columns to it.
     */
    protected function integration(): CustomerIntegration
    {
        return $this->integration;
    }
}
