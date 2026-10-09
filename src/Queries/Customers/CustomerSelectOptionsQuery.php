<?php

namespace Customers13\Queries\Customers;

use Customers13\Models\Customer;
use Customers13\Queries\ListQueryBase;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * @extends ListQueryBase<Customer>
 */
final class CustomerSelectOptionsQuery extends ListQueryBase
{
    /**
     * @return Collection<int, array{id: int, text: string}>
     */
    public function search(string $search): Collection
    {
        $pattern = $this->searchPattern($search);

        return Customer::query()
            ->where('active', true)
            ->when($search !== '', fn (Builder $query) => $query->whereRaw(
                'customers.name LIKE ? ESCAPE \''.self::LIKE_ESCAPE.'\'',
                [$pattern],
            ))
            ->orderBy('customers.name')
            ->orderBy('customers.id')
            ->limit(self::PER_PAGE)
            ->get(['id', 'name'])
            ->map(fn (Customer $customer): array => [
                'id' => $customer->id,
                'text' => $customer->name,
            ]);
    }
}
