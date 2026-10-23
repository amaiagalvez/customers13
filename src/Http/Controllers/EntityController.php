<?php

namespace Entities13\Http\Controllers;

use Illuminate\View\View;
use Entities13\Models\Entity;
use Entities13\Models\EntityType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\QueryException;
use Basics13\Http\Controllers\Controller;
use Entities13\Http\Requests\EntityRequest;
use Basics13\Support\Excel\ExcelExportService;
use Illuminate\Validation\ValidationException;
use Entities13\Http\Requests\EntityListRequest;
use Entities13\Queries\Entities\EntityListQuery;
use Entities13\Transformers\EntityListTransformer;
use Basics13\Support\Database\UniqueConstraintViolation;
use Entities13\Http\Requests\EntitySelectOptionsRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Entities13\Queries\Entities\EntitySelectOptionsQuery;

class EntityController extends Controller
{
    public function index(
        EntityListRequest $request,
        EntityListQuery $query,
        EntityListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $filters = $request->filters() ?? [];
        $entities = $query->active($search, $filters);

        return $this->listView($request, 'entities13::entities.list', [
            'entities' => $entities,
            // The form needs archived types too, so an edit can keep the ones the entity already
            // holds. The picker only shows those selected archived types, alongside active ones.
            'entityTypes' => EntityType::query()->orderBy('name_eu')->orderBy('id')->get(),
            'list' => $transformer->active(
                $entities,
                $search,
                $query->stateCounts(activeTotal: $search === '' && $filters === [] ? $entities->total() : null),
            ),
            'searchableFields' => $query->searchableFields('active'),
            'filters' => $filters,
            'scope' => 'active',
        ]);
    }

    public function export(
        EntityListRequest $request,
        EntityListQuery $query,
    ): BinaryFileResponse {
        $search = $request->search();
        $filters = $request->filters() ?? [];
        $scope = $request->scope();

        $queryBuilder = $query->filteredQuery($search, $filters, $scope);

        $columnDefinitions = [
            ['key' => 'id', 'label' => __('ID')],
            ['key' => 'name', 'label' => __('Name')],
            ['key' => 'notes', 'label' => __('Notes')],
            ['key' => 'active', 'label' => __('Status'), 'transformer' => fn ($e) => $e->active ? __('basics13::messages.active_label') : __('basics13::messages.inactive_label')],
            ['key' => 'created_at', 'label' => __('Created at')],
        ];

        return (new ExcelExportService(
            query: $queryBuilder,
            title: 'Entities Export',
            columnDefinitions: $columnDefinitions,
            maxRows: 50000,
        ))->download(ExcelExportService::fileName(__('Entities'), $scope));
    }

    public function selectOptions(
        EntitySelectOptionsRequest $request,
        EntitySelectOptionsQuery $query,
    ): JsonResponse {
        return response()->json([
            'results' => $query->search($request->search(), $request->user(), $request->context()),
        ]);
    }

    public function store(EntityRequest $request, EntityListQuery $query): RedirectResponse|JsonResponse
    {
        $name = $request->string('name')->toString();
        $deletedEntity = $query->findTrashedByName($name);

        if ($deletedEntity !== null && ! $request->boolean('reuse_deleted_name')) {
            return $this->deletedNameConflict($request, $deletedEntity, 'entities.index', 'deleted_entity_conflict');
        }

        try {
            $entity = DB::transaction(function () use ($request): Entity {
                $entity = Entity::create($this->attributes($request));
                $this->syncTypes($entity, $this->typeIds($request));

                return $entity;
            });
        } catch (QueryException $exception) {
            UniqueConstraintViolation::rethrowAsValidationError($exception);
        }

        if ($request->expectsJson()) {
            return response()->json($entity->only(['id', 'name']), 201);
        }

        return to_route('entities.index')->with('status', __('basics13::messages.created'));
    }

    public function update(EntityRequest $request, Entity $entity): RedirectResponse
    {
        try {
            DB::transaction(function () use ($request, $entity): void {
                $entity->update($this->attributes($request));
                $this->syncTypes($entity, $this->typeIds($request));
            });
        } catch (QueryException $exception) {
            UniqueConstraintViolation::rethrowAsValidationError($exception);
        }

        return to_route('entities.index')->with('status', __('basics13::messages.updated'));
    }

    /**
     * Sync entity types after locking them to prevent a race where a type is associated
     * after it has been soft-deleted. Callers run this inside their own transaction, which holds
     * the locks until the sync is written; a type trashed in the meantime is a validation error.
     *
     * @param  list<int>  $typeIds
     */
    private function syncTypes(Entity $entity, array $typeIds): void
    {
        if ($typeIds === []) {
            $entity->entityTypes()->sync([]);

            return;
        }

        // Lock the types to prevent concurrent soft-delete while we are associating them.
        $lockedTypes = EntityType::withTrashed()->whereKey($typeIds)->lockForUpdate()->get();

        if ($lockedTypes->contains(static fn (EntityType $type): bool => $type->trashed())) {
            throw ValidationException::withMessages([
                'entity_type_ids' => __('validation.exists', ['attribute' => __('Entity types')]),
            ]);
        }

        $entity->entityTypes()->sync($typeIds);
    }

    public function destroy(Entity $entity): RedirectResponse
    {
        $this->authorize('delete', $entity);

        // The deleting hook holds the row lock and refuses the delete while children exist, so the
        // controller only has to run it inside a transaction and report the outcome.
        $deleted = DB::transaction(static fn (): bool => $entity->delete() !== false);

        if (! $deleted) {
            return to_route('entities.index')
                ->with('error', __('Cannot be deleted while it has related records.'));
        }

        return to_route('entities.index')->with('status', __('basics13::messages.moved_to_trash'));
    }

    /**
     * The entity columns, without the types: those live in the pivot table and are written by the
     * relation, not assigned to the model.
     *
     * @return array<string, mixed>
     */
    private function attributes(EntityRequest $request): array
    {
        $attributes = $request->validated();
        unset($attributes['entity_type_ids']);

        return $attributes;
    }

    /**
     * The form always posts the whole set of checkboxes, so an absent field means a entity with no
     * types rather than one whose types were left alone. The pivot rows of a entity in the trash
     * are kept, which is what stops a type in use from being deleted.
     *
     * @return list<int>
     */
    private function typeIds(EntityRequest $request): array
    {
        /** @var list<int> $ids */
        $ids = array_map(
            static fn (mixed $id): int => (int) $id,
            (array) $request->validated('entity_type_ids', []),
        );

        return $ids;
    }
}
