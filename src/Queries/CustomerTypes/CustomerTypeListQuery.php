<?php

namespace Customers13\Queries\CustomerTypes;

use Basics13\Queries\ListQueryBase;
use Customers13\Models\CustomerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Customers13\Contracts\CustomerIntegration;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * @extends ListQueryBase<CustomerType>
 */
final class CustomerTypeListQuery extends ListQueryBase
{
    private const SCOPE_ACTIVE = 'active';

    private const SCOPE_ARCHIVED = 'archived';

    private const SCOPE_TRASHED = 'trashed';

    private const NAME_COLUMNS = ['CUM_customer_types.name_eu', 'CUM_customer_types.name_es', 'CUM_customer_types.name_fr', 'CUM_customer_types.name_en'];

    public function __construct(private readonly CustomerIntegration $integration) {}

    /**
     * @return LengthAwarePaginator<int, CustomerType>
     */
    public function active(string $search): LengthAwarePaginator
    {
        // Euskera names the type everywhere, so it is what the list is ordered by. The id breaks
        // ties the same way the archived and trash lists do.
        return $this->paginate(
            $this->withCustomersCount(CustomerType::query()
                ->where('CUM_customer_types.active', true)
                ->orderBy('CUM_customer_types.name_eu')
                ->orderBy('CUM_customer_types.id')),
            $search,
            searchColumns: self::NAME_COLUMNS,
        );
    }

    /**
     * @return LengthAwarePaginator<int, CustomerType>
     */
    public function archived(string $search): LengthAwarePaginator
    {
        return $this->paginate(
            $this->withCustomersCount(CustomerType::query()
                ->where('CUM_customer_types.active', false)
                ->orderBy('CUM_customer_types.name_eu')
                ->orderBy('CUM_customer_types.id')),
            $search,
            searchColumns: [...self::NAME_COLUMNS, 'CUM_customer_types.updated_at'],
        );
    }

    /**
     * @return LengthAwarePaginator<int, CustomerType>
     */
    public function trashed(string $search): LengthAwarePaginator
    {
        return $this->paginate(
            $this->withCustomersCount(CustomerType::onlyTrashed()
                ->latest('deleted_at')
                ->orderBy('id')),
            $search,
            searchColumns: [...self::NAME_COLUMNS, 'CUM_customer_types.deleted_at'],
        );
    }

    /**
     * @return class-string<CustomerType>
     */
    protected function getModelClass(): string
    {
        return CustomerType::class;
    }

    /**
     * The type list joins nothing, so the integration contributes no columns to it. The accessor is
     * implemented because the base declares it, and kept injected so every list query of this
     * package takes its integration the same way.
     */
    protected function integration(): CustomerIntegration
    {
        return $this->integration;
    }

    /**
     * A trashed type is found by its euskera name, which is the one the create form names first and
     * the only name a type is required to have.
     *
     * @param  Builder<CustomerType>  $query
     */
    protected function firstTrashedByName(Builder $query, string $name): ?Model
    {
        return $query->where('name_eu', $name)->latest('deleted_at')->first();
    }

    /**
     * How many customers hold the type, deleted ones included: a type in the trash keeps the pivot
     * rows of the customers that held it, and that count is what the list needs to know before it
     * offers to delete a type instead of archiving it.
     *
     * @param  Builder<CustomerType>  $query
     * @return Builder<CustomerType>
     */
    private function withCustomersCount(Builder $query): Builder
    {
        return $query->withCount([
            'customers' => fn (Builder $customers): Builder => $customers
                ->withoutGlobalScope(SoftDeletingScope::class),
        ]);
    }
}
