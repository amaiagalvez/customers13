<?php

namespace Customers13\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

/**
 * What `customers13` does when no integration package is installed: a customer row carries only
 * what the customer itself knows. Registered as the default binding so no consumer has to check
 * whether an integration exists.
 */
final class NullCustomerIntegration implements CustomerIntegration
{
    public function scope(Builder $query, string $scope, string $alias): void
    {
        //
    }

    /**
     * @param  list<string>  $columns
     * @return list<string>
     */
    public function searchColumns(string $scope, array $columns): array
    {
        return $columns;
    }

    /** @return array<string, mixed> */
    public function row(Model $record): array
    {
        return [];
    }
}
