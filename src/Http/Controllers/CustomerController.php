<?php

namespace Customers13\Http\Controllers;

use Illuminate\View\View;
use Customers13\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\QueryException;
use Basics13\Http\Controllers\Controller;
use Customers13\Http\Requests\CustomerRequest;
use Customers13\Http\Requests\CustomerListRequest;
use Customers13\Queries\Customers\CustomerListQuery;
use Customers13\Transformers\CustomerListTransformer;
use Basics13\Support\Database\UniqueConstraintViolation;
use Customers13\Http\Requests\CustomerSelectOptionsRequest;
use Customers13\Queries\Customers\CustomerSelectOptionsQuery;

class CustomerController extends Controller
{
    public function index(
        CustomerListRequest $request,
        CustomerListQuery $query,
        CustomerListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $customers = $query->active($search);

        return $this->listView($request, 'customers13::customers.list', [
            'customers' => $customers,
            'list' => $transformer->active(
                $customers,
                $search,
                $query->stateCounts(activeTotal: $search === '' ? $customers->total() : null),
            ),
        ]);
    }

    public function selectOptions(
        CustomerSelectOptionsRequest $request,
        CustomerSelectOptionsQuery $query,
    ): JsonResponse {
        return response()->json([
            'results' => $query->search($request->search()),
        ]);
    }

    public function store(CustomerRequest $request, CustomerListQuery $query): RedirectResponse|JsonResponse
    {
        $name = $request->string('name')->toString();
        $deletedCustomer = $query->findTrashedByName($name);

        if ($deletedCustomer !== null && ! $request->boolean('reuse_deleted_name')) {
            return $this->deletedNameConflict($request, $deletedCustomer, 'customers.index', 'deleted_customer_conflict');
        }

        try {
            $customer = Customer::create($request->validated());
        } catch (QueryException $exception) {
            UniqueConstraintViolation::rethrowAsValidationError($exception);
        }

        if ($request->expectsJson()) {
            return response()->json($customer->only(['id', 'name']), 201);
        }

        return to_route('customers.index')->with('status', __('customers13::messages.created'));
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        try {
            $customer->update($request->validated());
        } catch (QueryException $exception) {
            UniqueConstraintViolation::rethrowAsValidationError($exception);
        }

        return to_route('customers.index')->with('status', __('customers13::messages.updated'));
    }

    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize('delete', $customer);

        // The deleting hook holds the row lock and refuses the delete while children exist, so the
        // controller only has to run it inside a transaction and report the outcome.
        $deleted = DB::transaction(static fn (): bool => $customer->delete() !== false);

        if (! $deleted) {
            return to_route('customers.index')
                ->with('error', __('Cannot be deleted while it has related records.'));
        }

        return to_route('customers.index')->with('status', __('customers13::messages.moved_to_trash'));
    }
}
