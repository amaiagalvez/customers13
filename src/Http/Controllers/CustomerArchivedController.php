<?php

namespace Customers13\Http\Controllers;

use Illuminate\View\View;
use Customers13\Models\Customer;
use Illuminate\Http\RedirectResponse;
use Customers13\Http\Requests\CustomerListRequest;
use Customers13\Queries\Customers\CustomerListQuery;
use Customers13\Transformers\CustomerListTransformer;

/**
 * @extends ArchivedController<Customer>
 */
class CustomerArchivedController extends \Basics13\Http\Controllers\ArchivedController
{
    public function index(
        CustomerListRequest $request,
        CustomerListQuery $query,
        CustomerListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $customers = $query->archived($search);

        return $this->listView($request, 'customers13::customers.list', [
            'customers' => $customers,
            'list' => $transformer->archived(
                $customers,
                $search,
                $query->stateCounts(archivedTotal: $search === '' ? $customers->total() : null),
            ),
        ]);
    }

    public function archive(Customer $customer): RedirectResponse
    {
        return $this->archiveRecord($customer);
    }

    public function activate(Customer $customer): RedirectResponse
    {
        return $this->activateRecord($customer);
    }

    protected function activeRoute(): string
    {
        return 'customers.index';
    }

    protected function archivedRoute(): string
    {
        return 'customers.archived.index';
    }
}
