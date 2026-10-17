<?php

namespace Customers13\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Customers13\Models\CustomerType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\QueryException;
use Basics13\Http\Controllers\Controller;
use Customers13\Http\Requests\CustomerTypeRequest;
use Customers13\Http\Requests\CustomerTypeListRequest;
use Basics13\Support\Database\UniqueConstraintViolation;
use Customers13\Transformers\CustomerTypeListTransformer;
use Customers13\Queries\CustomerTypes\CustomerTypeListQuery;
use Customers13\Http\Requests\CustomerTypeSelectOptionsRequest;
use Customers13\Queries\CustomerTypes\CustomerTypeSelectOptionsQuery;

class CustomerTypeController extends Controller
{
    /**
     * The three original names plus the euskera one, in the order a duplicate is looked for when
     * the database refuses a write that validation let through.
     */
    private const NAME_FIELDS = ['name_eu', 'name_es', 'name_fr', 'name_en'];

    public function index(
        CustomerTypeListRequest $request,
        CustomerTypeListQuery $query,
        CustomerTypeListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $customerTypes = $query->active($search);

        return $this->listView($request, 'customers13::customer-types.list', [
            'customerTypes' => $customerTypes,
            'list' => $transformer->active(
                $customerTypes,
                $search,
                $query->stateCounts(activeTotal: $search === '' ? $customerTypes->total() : null),
            ),
        ]);
    }

    public function selectOptions(
        CustomerTypeSelectOptionsRequest $request,
        CustomerTypeSelectOptionsQuery $query,
    ): JsonResponse {
        return response()->json([
            'results' => $query->search($request->search()),
        ]);
    }

    public function store(CustomerTypeRequest $request, CustomerTypeListQuery $query): RedirectResponse|JsonResponse
    {
        $name = $request->string('name_eu')->toString();
        $deletedType = $query->findTrashedByName($name);

        if ($deletedType !== null && ! $request->boolean('reuse_deleted_name')) {
            return $this->trashedTypeConflict($request, $deletedType);
        }

        try {
            $customerType = CustomerType::create($request->validated());
        } catch (QueryException $exception) {
            $this->failOnDuplicatedName($exception);
        }

        if ($request->expectsJson()) {
            return response()->json($customerType->only(['id', 'name_eu']), 201);
        }

        return to_route('customer-types.index')->with('status', __('basics13::messages.created'));
    }

    public function update(CustomerTypeRequest $request, CustomerType $customerType): RedirectResponse
    {
        try {
            $customerType->update($request->validated());
        } catch (QueryException $exception) {
            $this->failOnDuplicatedName($exception);
        }

        return to_route('customer-types.index')->with('status', __('basics13::messages.updated'));
    }

    public function destroy(CustomerType $customerType): RedirectResponse
    {
        $this->authorize('delete', $customerType);

        // The deleting hook holds the row lock and refuses the delete while a customer holds the
        // type, so the controller only has to run it inside a transaction and report the outcome.
        $deleted = DB::transaction(static fn (): bool => $customerType->delete() !== false);

        if (! $deleted) {
            return to_route('customer-types.index')
                ->with('error', __('basics13::messages.cannot_delete_related'));
        }

        return to_route('customer-types.index')->with('status', __('basics13::messages.moved_to_trash'));
    }

    /**
     * A trashed type already uses the euskera name the user is creating. The browser is sent back to
     * the list with the conflict modal open; a JSON caller gets a 409 it can render on the field.
     *
     * A type is named in four languages and the conflict modal offers to create it with the names
     * the trashed one had, so the whole set travels in the payload instead of the single `name` the
     * generic conflict of the base controller carries.
     */
    private function trashedTypeConflict(
        CustomerTypeRequest $request,
        CustomerType $trashedType,
    ): RedirectResponse|JsonResponse {
        $payload = [
            'id' => $trashedType->getKey(),
            ...$trashedType->only(['name_eu', 'name_es', 'name_fr', 'name_en']),
        ];

        if ($request->expectsJson()) {
            $message = __('basics13::messages.deleted_name_conflict', ['name' => $trashedType->name_eu]);

            return response()->json([
                'message' => $message,
                'errors' => ['name_eu' => [$message]],
            ], 409);
        }

        return to_route('customer-types.index')
            ->withInput()
            ->with('deleted_customer_type_conflict', $payload);
    }

    /**
     * A write that passed validation and still hit a unique index lost a race against a concurrent
     * one, which the request cannot have seen. The index the database names is what tells which of
     * the four languages collided.
     */
    private function failOnDuplicatedName(QueryException $exception): never
    {
        UniqueConstraintViolation::rethrowAsValidationError($exception, $this->duplicatedNameField($exception));
    }

    /**
     * MySQL names the index of the colliding language (`..._active_name_es_unique`), while the
     * partial index SQLite and PostgreSQL build names the bare column (`...name_es`), so both
     * spellings are looked for.
     */
    private function duplicatedNameField(QueryException $exception): string
    {
        $message = (string) ($exception->errorInfo[2] ?? $exception->getMessage());

        foreach (self::NAME_FIELDS as $field) {
            if (str_contains($message, 'active_'.$field) || str_contains($message, '.'.$field)) {
                return $field;
            }
        }

        return 'name_eu';
    }
}
