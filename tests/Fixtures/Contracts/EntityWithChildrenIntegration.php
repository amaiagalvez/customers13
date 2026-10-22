<?php

namespace Entities13\Tests\Fixtures\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Entities13\Contracts\EntityIntegration;

/**
 * Stands in for any integration package that knows something about a entity this package cannot
 * see on its own. It answers the three contract methods and reports that something hangs from every
 * entity, which is the row key the active list reads to offer the archive action instead of the
 * delete one. What that something is belongs to the integration, so the key is all this fake needs.
 */
class EntityWithChildrenIntegration extends EntityIntegration
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
        return ['blocks_delete' => true];
    }
}
