<?php

namespace Entities13\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Contracts\Auth\Authenticatable;

/**
 * The one seam between `entities13` and whatever optional package knows about a entity beyond
 * what this package can see on its own.
 *
 * `entities13` registers this abstract class by default via `singletonIf`, so the entity lists
 * show no counts, no chips and no links to anything that does not exist here. An integration package
 * binds its own implementation in its service provider by extending this class.
 *
 * The delete guard is deliberately not part of this contract: an integration that needs to refuse a
 * delete registers its own `Entity::deleting()` hook, which is one line and keeps the lock
 * semantics in the hands of whoever owns the rule.
 */
abstract class EntityIntegration
{
    /**
     * Contribute to the entity list query. Called before the base query searches or orders, so
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
     * Extra fields for a entity list row, merged over the base ones. `blocks_delete` set to true
     * turns the row's delete action into an archive one, because something hangs from the entity.
     *
     * @return array<string, mixed>
     */
    public function row(Model $record): array
    {
        return [];
    }

    /**
     * Whether this user may pick entities from the options endpoint without being allowed to list
     * them, because another form of the integration needs an entity chosen. Only active entities
     * are offered either way.
     */
    public function allowsSelectOptions(Authenticatable $user): bool
    {
        return false;
    }
}
