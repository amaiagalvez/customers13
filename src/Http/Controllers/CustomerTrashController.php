<?php

namespace Customers13\Http\Controllers;

use Illuminate\View\View;
use Customers13\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\Eloquent\Model;
use Basics13\Http\Controllers\TrashController;
use Customers13\Http\Requests\CustomerListRequest;
use Customers13\Queries\Customers\CustomerListQuery;
use Customers13\Http\Requests\CustomerDestroyRequest;
use Customers13\Http\Requests\CustomerRestoreRequest;
use Customers13\Transformers\CustomerListTransformer;

/**
 * @extends TrashController<Customer>
 */
class CustomerTrashController extends TrashController
{
    public function index(
        CustomerListRequest $request,
        CustomerListQuery $query,
        CustomerListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $customers = $query->trashed($search);

        return $this->listView($request, 'customers13::customers.list', [
            'customers' => $customers,
            'list' => $transformer->trash(
                $customers,
                $search,
                $query->stateCounts(trashedTotal: $search === '' ? $customers->total() : null),
            ),
        ]);
    }

    public function restore(CustomerRestoreRequest $request): RedirectResponse
    {
        return $this->restoreTrashed($request);
    }

    public function destroy(CustomerDestroyRequest $request): RedirectResponse
    {
        return $this->destroyTrashed($request);
    }

    /**
     * @param  Customer  $record
     */
    protected function restoreTrashedRecord(Model $record): void
    {
        $record->restore();
    }

    /**
     * @param  Customer  $record
     */
    protected function nameIsTaken(Model $record): bool
    {
        return $this->takenBy(Customer::query(), $record->name);
    }

    protected function recordClass(): string
    {
        return Customer::class;
    }

    protected function trashRoute(): string
    {
        return 'customers.trash.index';
    }
}
