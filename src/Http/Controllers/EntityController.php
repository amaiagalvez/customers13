<?php

namespace entities13\Http\Controllers;

use Illuminate\View\View;
use entities13\Models\Entity;
use entities13\Models\EntityType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Database\QueryException;
use Basics13\Http\Controllers\Controller;
use entities13\Http\Requests\EntityRequest;
use entities13\Http\Requests\EntityListRequest;
use entities13\Queries\Entities\EntityListQuery;
use entities13\Transformers\EntityListTransformer;
use Basics13\Support\Database\UniqueConstraintViolation;
use entities13\Http\Requests\EntitySelectOptionsRequest;
use entities13\Queries\Entities\EntitySelectOptionsQuery;

class EntityController extends Controller
{
    public function index(
        EntityListRequest $request,
        EntityListQuery $query,
        EntityListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $entities = $query->active($search);

        return $this->listView($request, 'entities13::entities.list', [
            'entities' => $entities,
            // The form needs archived types too, so an edit can keep the ones the entity already
            // holds. The picker only shows those selected archived types, alongside active ones.
            'entityTypes' => EntityType::query()->orderBy('name_eu')->orderBy('id')->get(),
            'list' => $transformer->active(
                $entities,
                $search,
                $query->stateCounts(activeTotal: $search === '' ? $entities->total() : null),
            ),
        ]);
    }

    public function selectOptions(
        EntitySelectOptionsRequest $request,
        EntitySelectOptionsQuery $query,
    ): JsonResponse {
        return response()->json([
            'results' => $query->search($request->search()),
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
     * after it has been soft-deleted.
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
        $lockedTypes = DB::transaction(static fn (): array => EntityType::withTrashed()
            ->whereKey($typeIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('id')
            ->all());

        // Ensure none of the types are soft-deleted.
        foreach ($typeIds as $id) {
            if (isset($lockedTypes[$id]) && $lockedTypes[$id]->trashed()) {
                throw new \RuntimeException('Cannot associate a deleted entity type.');
            }
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
