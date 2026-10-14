<?php

namespace Customers13\Queries\Customers;

use Customers13\Models\Customer;
use Illuminate\Support\Collection;
use Basics13\Queries\ListQueryBase;
use Illuminate\Database\Eloquent\Builder;
use Customers13\Contracts\CustomerIntegration;

/**
 * @extends ListQueryBase<Customer>
 */
final class CustomerSelectOptionsQuery extends ListQueryBase
{
    public function __construct(private readonly CustomerIntegration $integration) {}

    /**
     * @return Collection<int, array{id: int, text: string}>
     */
    public function search(string $search): Collection
    {
        $pattern = $this->searchPattern($search);

        return Customer::query()
            ->where('active', true)
            ->when($search !== '', fn (Builder $query) => $query->whereRaw(
                'CUM_customers.name LIKE ? ESCAPE \''.self::LIKE_ESCAPE.'\'',
                [$pattern],
            ))
            ->orderBy('CUM_customers.name')
            ->orderBy('CUM_customers.id')
            ->limit(self::PER_PAGE)
            ->get(['id', 'name'])
            ->map(fn (Customer $customer): array => [
                'id' => $customer->id,
                'text' => $customer->name,
            ]);
    }

    /**
     * @return class-string<Customer>
     */
    protected function getModelClass(): string
    {
        return Customer::class;
    }

    protected function integration(): CustomerIntegration
    {
        return $this->integration;
    }
}
