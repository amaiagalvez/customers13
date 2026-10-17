<?php

namespace Customers13\Http\Controllers;

use Illuminate\View\View;
use Customers13\Models\CustomerType;
use Illuminate\Http\RedirectResponse;
use Basics13\Http\Controllers\ArchivedController;
use Customers13\Http\Requests\CustomerTypeListRequest;
use Customers13\Transformers\CustomerTypeListTransformer;
use Customers13\Queries\CustomerTypes\CustomerTypeListQuery;

class CustomerTypeArchivedController extends ArchivedController
{
    public function index(
        CustomerTypeListRequest $request,
        CustomerTypeListQuery $query,
        CustomerTypeListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $customerTypes = $query->archived($search);

        return $this->listView($request, 'customers13::customer-types.list', [
            'customerTypes' => $customerTypes,
            'list' => $transformer->archived(
                $customerTypes,
                $search,
                $query->stateCounts(archivedTotal: $search === '' ? $customerTypes->total() : null),
            ),
        ]);
    }

    public function archive(CustomerType $customerType): RedirectResponse
    {
        return $this->archiveRecord($customerType);
    }

    public function activate(CustomerType $customerType): RedirectResponse
    {
        return $this->activateRecord($customerType);
    }

    protected function activeRoute(): string
    {
        return 'customer-types.index';
    }

    protected function archivedRoute(): string
    {
        return 'customer-types.archived.index';
    }
}
