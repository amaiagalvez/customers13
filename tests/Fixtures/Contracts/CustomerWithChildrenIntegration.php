<?php

namespace Customers13\Tests\Fixtures\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Customers13\Contracts\CustomerIntegration;

/**
 * Stands in for any integration package that knows something about a customer this package cannot
 * see on its own. It answers the three contract methods and reports that something hangs from every
 * customer, which is the row key the active list reads to offer the archive action instead of the
 * delete one. What that something is belongs to the integration, so the key is all this fake needs.
 */
class CustomerWithChildrenIntegration implements CustomerIntegration
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
        return ['projects_exists' => true];
    }
}
