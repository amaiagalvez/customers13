<?php

namespace entities13\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use entities13\Models\EntityType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\QueryException;
use Basics13\Http\Controllers\Controller;
use entities13\Http\Requests\EntityTypeRequest;
use entities13\Http\Requests\EntityTypeListRequest;
use Basics13\Support\Database\UniqueConstraintViolation;
use entities13\Transformers\EntityTypeListTransformer;
use entities13\Queries\EntityTypes\EntityTypeListQuery;
use entities13\Http\Requests\EntityTypeSelectOptionsRequest;
use entities13\Queries\EntityTypes\EntityTypeSelectOptionsQuery;

class EntityTypeController extends Controller
{
    public function index(
        EntityTypeListRequest $request,
        EntityTypeListQuery $query,
        EntityTypeListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $entityTypes = $query->active($search);

        return $this->listView($request, 'entities13::entity-types.list', [
            'entityTypes' => $entityTypes,
            'list' => $transformer->active(
                $entityTypes,
                $search,
                $query->stateCounts(activeTotal: $search === '' ? $entityTypes->total() : null),
            ),
        ]);
    }

    public function selectOptions(
        EntityTypeSelectOptionsRequest $request,
        EntityTypeSelectOptionsQuery $query,
    ): JsonResponse {
        return response()->json([
            'results' => $query->search($request->search()),
        ]);
    }

    public function store(EntityTypeRequest $request, EntityTypeListQuery $query): RedirectResponse|JsonResponse
    {
        $name = $request->string('name_eu')->toString();
        $deletedType = $query->findTrashedByName($name);

        if ($deletedType !== null && ! $request->boolean('reuse_deleted_name')) {
            return $this->trashedTypeConflict($request, $deletedType);
        }

        try {
            $entityType = EntityType::create($request->validated());
        } catch (QueryException $exception) {
            $this->failOnDuplicatedName($exception);
        }

        if ($request->expectsJson()) {
            return response()->json($entityType->only(['id', 'name_eu']), 201);
        }

        return to_route('entity-types.index')->with('status', __('basics13::messages.created'));
    }

    public function update(EntityTypeRequest $request, EntityType $entityType): RedirectResponse
    {
        try {
            $entityType->update($request->validated());
        } catch (QueryException $exception) {
            $this->failOnDuplicatedName($exception);
        }

        return to_route('entity-types.index')->with('status', __('basics13::messages.updated'));
    }

    public function destroy(EntityType $entityType): RedirectResponse
    {
        $this->authorize('delete', $entityType);

        // The deleting hook holds the row lock and refuses the delete while a entity holds the
        // type, so the controller only has to run it inside a transaction and report the outcome.
        $deleted = DB::transaction(static fn(): bool => $entityType->delete() !== false);

        if (! $deleted) {
            return to_route('entity-types.index')
                ->with('error', __('basics13::messages.cannot_delete_related'));
        }

        return to_route('entity-types.index')->with('status', __('basics13::messages.moved_to_trash'));
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
        EntityTypeRequest $request,
        EntityType $trashedType,
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

        return to_route('entity-types.index')
            ->withInput()
            ->with('deleted_entity_type_conflict', $payload);
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

        foreach (EntityType::NAME_FIELDS as $field) {
            if (str_contains($message, 'active_' . $field) || str_contains($message, '.' . $field)) {
                return $field;
            }
        }

        return 'name_eu';
    }
}
