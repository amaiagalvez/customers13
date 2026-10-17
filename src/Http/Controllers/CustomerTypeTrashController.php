<?php

namespace Customers13\Http\Controllers;

use Illuminate\View\View;
use Customers13\Models\CustomerType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Controllers\TrashController;
use Customers13\Http\Requests\CustomerTypeListRequest;
use Customers13\Http\Requests\CustomerTypeDestroyRequest;
use Customers13\Http\Requests\CustomerTypeRestoreRequest;
use Customers13\Transformers\CustomerTypeListTransformer;
use Customers13\Queries\CustomerTypes\CustomerTypeListQuery;

/**
 * @extends TrashController<CustomerType>
 */
class CustomerTypeTrashController extends TrashController
{
    public function index(
        CustomerTypeListRequest $request,
        CustomerTypeListQuery $query,
        CustomerTypeListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $customerTypes = $query->trashed($search);

        return $this->listView($request, 'customers13::customer-types.list', [
            'customerTypes' => $customerTypes,
            'list' => $transformer->trash(
                $customerTypes,
                $search,
                $query->stateCounts(trashedTotal: $search === '' ? $customerTypes->total() : null),
            ),
        ]);
    }

    public function restore(CustomerTypeRestoreRequest $request): RedirectResponse
    {
        return $this->restoreTrashed($request);
    }

    public function destroy(CustomerTypeDestroyRequest $request): RedirectResponse
    {
        return $this->destroyTrashed($request);
    }

    /**
     * @param  CustomerType  $record
     */
    protected function restoreTrashedRecord(Model $record): void
    {
        $record->restore();
    }

    /**
     * A type is named in four languages and restoring it puts all four back, so any of them being
     * taken by a live type makes the restore impossible.
     *
     * @param  CustomerType  $record
     */
    protected function nameIsTaken(Model $record): bool
    {
        foreach (['name_eu', 'name_es', 'name_fr', 'name_en'] as $column) {
            $name = $record->getAttribute($column);

            if ($name !== null && CustomerType::query()->where($column, $name)->exists()) {
                return true;
            }
        }

        return false;
    }

    protected function recordClass(): string
    {
        return CustomerType::class;
    }

    protected function trashRoute(): string
    {
        return 'customer-types.trash.index';
    }
}
