<?php

namespace Customers13\Http\Controllers;

use Illuminate\View\View;
use Customers13\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Customers13\Models\CustomerType;
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
            // The form needs archived types too, so an edit can keep the ones the customer already
            // holds. The picker only shows those selected archived types, alongside active ones.
            'customerTypes' => CustomerType::query()->orderBy('name_eu')->orderBy('id')->get(),
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
            $customer = DB::transaction(function () use ($request): Customer {
                $customer = Customer::create($this->attributes($request));
                $this->syncTypes($customer, $this->typeIds($request));

                return $customer;
            });
        } catch (QueryException $exception) {
            UniqueConstraintViolation::rethrowAsValidationError($exception);
        }

        if ($request->expectsJson()) {
            return response()->json($customer->only(['id', 'name']), 201);
        }

        return to_route('customers.index')->with('status', __('basics13::messages.created'));
    }

    public function update(CustomerRequest $request, Customer $customer): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $customer): void {
                $customer->update($this->attributes($request));
                $this->syncTypes($customer, $this->typeIds($request));
            });
        } catch (QueryException $exception) {
            UniqueConstraintViolation::rethrowAsValidationError($exception);
        }

        return to_route('customers.index')->with('status', __('basics13::messages.updated'));
    }

    /**
     * Sync customer types after locking them to prevent a race where a type is associated
     * after it has been soft-deleted.
     *
     * @param  list<int>  $typeIds
     */
    private function syncTypes(Customer $customer, array $typeIds): void
    {
        if ($typeIds === []) {
            $customer->customerTypes()->sync([]);

            return;
        }

        // Lock the types to prevent concurrent soft-delete while we are associating them.
        $lockedTypes = DB::transaction(static fn (): array => CustomerType::withTrashed()
            ->whereKey($typeIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id')
            ->all());

        // Ensure none of the types are soft-deleted.
        foreach ($typeIds as $id) {
            if (isset($lockedTypes[$id]) && $lockedTypes[$id]->trashed()) {
                throw new \RuntimeException('Cannot associate a deleted customer type.');
            }
        }

        $customer->customerTypes()->sync($typeIds);
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

        return to_route('customers.index')->with('status', __('basics13::messages.moved_to_trash'));
    }

    /**
     * The customer columns, without the types: those live in the pivot table and are written by the
     * relation, not assigned to the model.
     *
     * @return array<string, mixed>
     */
    private function attributes(CustomerRequest $request): array
    {
        $attributes = $request->validated();
        unset($attributes['customer_type_ids']);

        return $attributes;
    }

    /**
     * The form always posts the whole set of checkboxes, so an absent field means a customer with no
     * types rather than one whose types were left alone. The pivot rows of a customer in the trash
     * are kept, which is what stops a type in use from being deleted.
     *
     * @return list<int>
     */
    private function typeIds(CustomerRequest $request): array
    {
        /** @var list<int> $ids */
        $ids = array_map(
            static fn (mixed $id): int => (int) $id,
            (array) $request->validated('customer_type_ids', []),
        );

        return $ids;
    }
}
