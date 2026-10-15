<?php

namespace Customers13\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * The one seam between `customers13` and whatever optional package knows about a customer beyond
 * what this package can see on its own.
 *
 * `customers13` registers this abstract class by default via `singletonIf`, so the customer lists
 * show no counts, no chips and no links to anything that does not exist here. An integration package
 * binds its own implementation in its service provider by extending this class.
 *
 * The delete guard is deliberately not part of this contract: an integration that needs to refuse a
 * delete registers its own `Customer::deleting()` hook, which is one line and keeps the lock
 * semantics in the hands of whoever owns the rule.
 */
abstract class CustomerIntegration
{
    /**
     * Contribute to the customer list query. Called before the base query searches or orders, so
     * the columns contributed by `searchColumns()` already exist.
     *
     * @param  Builder<covariant Model>  $query
     */
    public function scope(Builder $query, string $scope, string $alias): void
    {
        //
    }

    /**
     * Columns this integration contributes to the search, appended to the base ones. The base
     * search order must stay the same when the integration returns nothing.
     *
     * @param  list<string>  $columns
     * @return list<string>
     */
    public function searchColumns(string $scope, array $columns): array
    {
        return $columns;
    }

    /**
     * Extra fields for a customer list row, merged over the base ones.
     *
     * @return array<string, mixed>
     */
    public function row(Model $record): array
    {
        return [];
    }
}
