<?php

namespace Customers13\Queries\Customers;

use Customers13\Models\Customer;
use Basics13\Queries\ListQueryBase;
use Illuminate\Database\Eloquent\Builder;
use Customers13\Contracts\CustomerIntegration;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @extends ListQueryBase<Customer>
 */
final class CustomerListQuery extends ListQueryBase
{
    /**
     * The list the columns below belong to. Passed to the integration so one binding can tell an
     * active row from an archived or trashed one, which is all it needs to count the right slice.
     */
    private const SCOPE_ACTIVE = 'active';

    private const SCOPE_ARCHIVED = 'archived';

    private const SCOPE_TRASHED = 'trashed';

    public function __construct(private readonly CustomerIntegration $integration) {}

    /**
     * @return LengthAwarePaginator<int, Customer>
     */
    public function active(string $search): LengthAwarePaginator
    {
        // Unique names among non-deleted customers keep the ordering total on its own; the id
        // is here so this list breaks ties the same way the archived and trash lists do.
        return $this->paginate(
            $this->scoped(self::SCOPE_ACTIVE, Customer::query()->where('CUM_customers.active', true)->orderBy('CUM_customers.name')->orderBy('CUM_customers.id')),
            $search,
            /** @phpstan-ignore argument.type */
            searchColumns: $this->searchColumns(self::SCOPE_ACTIVE, ['CUM_customers.name']),
        );
    }

    /**
     * @return LengthAwarePaginator<int, Customer>
     */
    public function archived(string $search): LengthAwarePaginator
    {
        return $this->paginate(
            $this->scoped(self::SCOPE_ARCHIVED, Customer::query()->where('CUM_customers.active', false)->orderBy('CUM_customers.name')->orderBy('CUM_customers.id')),
            $search,
            /** @phpstan-ignore argument.type */
            searchColumns: $this->searchColumns(self::SCOPE_ARCHIVED, ['CUM_customers.name', 'CUM_customers.updated_at']),
        );
    }

    /**
     * @return LengthAwarePaginator<int, Customer>
     */
    public function trashed(string $search): LengthAwarePaginator
    {
        return $this->paginate(
            $this->scoped(self::SCOPE_TRASHED, Customer::onlyTrashed()->latest('deleted_at')->orderBy('id')),
            $search,
            /** @phpstan-ignore argument.type */
            searchColumns: $this->searchColumns(self::SCOPE_TRASHED, ['CUM_customers.name', 'CUM_customers.deleted_at']),
        );
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

    /**
     * Hand the query to the integration before anything searches or orders it, so the columns it
     * contributes to the search and the ordering are already there.
     *
     * @param  Builder<Customer>  $query
     * @return Builder<Customer>
     */
    private function scoped(string $scope, Builder $query): Builder
    {
        $this->integration->scope($query, $scope, 'customer_projects');

        return $query;
    }
}
