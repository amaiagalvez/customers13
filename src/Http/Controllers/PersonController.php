<?php

namespace Entities13\Http\Controllers;

use Illuminate\View\View;
use Entities13\Models\Entity;
use Entities13\Models\Person;
use Entities13\Models\PersonType;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Basics13\Http\Controllers\Controller;
use Entities13\Http\Requests\PersonRequest;
use Basics13\Support\Excel\ExcelExportService;
use Illuminate\Validation\ValidationException;
use Entities13\Http\Requests\PersonListRequest;
use Entities13\Queries\Persons\PersonListQuery;
use Entities13\Transformers\PersonListTransformer;
use Entities13\Http\Requests\PersonSelectOptionsRequest;
use Entities13\Queries\Persons\PersonSelectOptionsQuery;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class PersonController extends Controller
{
    public function index(
        PersonListRequest $request,
        PersonListQuery $query,
        PersonListTransformer $transformer,
    ): View|string {
        $search = $request->search();
        $filters = $request->filters() ?? [];
        $persons = $query->active($search, $filters);

        return $this->listView($request, 'entities13::persons.list', [
            'persons' => $persons,
            // The form needs archived entities and types too, so an edit can keep the ones the
            // person already holds. The pickers only show those, alongside the active ones.
            'entities' => Entity::query()->orderBy('name')->orderBy('id')->get(['id', 'name', 'active']),
            'personTypes' => PersonType::query()->orderBy('name_eu')->orderBy('id')->get(['id', 'name_eu', 'active']),
            'list' => $transformer->active(
                $persons,
                $search,
                $query->stateCounts(activeTotal: $search === '' && $filters === [] ? $persons->total() : null),
            ),
            'searchableFields' => $query->searchableFields('active'),
            'filters' => $filters,
            'scope' => 'active',
        ]);
    }

    public function selectOptions(
        PersonSelectOptionsRequest $request,
        PersonSelectOptionsQuery $query,
    ): JsonResponse {
        return response()->json([
            'results' => $query->search($request->search()),
        ]);
    }

    public function store(PersonRequest $request): RedirectResponse|JsonResponse
    {
        $person = DB::transaction(function () use ($request): Person {
            $person = Person::create($this->attributes($request));
            $this->syncTypes($person, $this->typeIds($request));

            return $person;
        });

        if ($request->expectsJson()) {
            return response()->json($person->only(['id', 'name', 'last_name_1', 'last_name_2']), 201);
        }

        return $this->backToList('persons.index')->with('status', __('basics13::messages.created'));
    }

    public function update(PersonRequest $request, Person $person): RedirectResponse
    {
        DB::transaction(function () use ($request, $person): void {
            $person->update($this->attributes($request));
            $this->syncTypes($person, $this->typeIds($request));
        });

        return $this->backToList('persons.index')->with('status', __('basics13::messages.updated'));
    }

    /**
     * Sync person types after locking them to prevent a race where a type is associated after it
     * has been soft-deleted. Callers run this inside their own transaction, which holds the locks
     * until the sync is written; a type trashed in the meantime is a validation error.
     *
     * @param  list<int>  $typeIds
     */
    private function syncTypes(Person $person, array $typeIds): void
    {
        if ($typeIds === []) {
            $person->personTypes()->sync([]);

            return;
        }

        $lockedTypes = PersonType::withTrashed()->whereKey($typeIds)->lockForUpdate()->get();

        if ($lockedTypes->contains(static fn (PersonType $type): bool => $type->trashed())) {
            throw ValidationException::withMessages([
                'person_type_ids' => __('validation.exists', ['attribute' => __('Person types')]),
            ]);
        }

        $person->personTypes()->sync($typeIds);
    }

    /**
     * The person columns, without the types: those live in the pivot table and are written by the
     * relation, not assigned to the model.
     *
     * @return array<string, mixed>
     */
    private function attributes(PersonRequest $request): array
    {
        $attributes = $request->validated();
        unset($attributes['person_type_ids']);

        return $attributes;
    }

    /**
     * The form always posts the whole set of checkboxes, so an absent field means a person with no
     * types rather than one whose types were left alone.
     *
     * @return list<int>
     */
    private function typeIds(PersonRequest $request): array
    {
        /** @var list<int> $ids */
        $ids = array_map(
            static fn (mixed $id): int => (int) $id,
            (array) $request->validated('person_type_ids', []),
        );

        return $ids;
    }

    public function destroy(Person $person): RedirectResponse
    {
        $this->authorize('delete', $person);
        $person->delete();

        return $this->backToList('persons.index')->with('status', __('basics13::messages.moved_to_trash'));
    }

    public function export(PersonListRequest $request, PersonListQuery $query): BinaryFileResponse
    {
        $this->authorize('viewAny', Person::class);

        $search = $request->search();
        $filters = $request->filters() ?? [];
        $scope = $request->scope();

        $queryBuilder = $query->filteredQuery($search, $filters, $scope);

        $columnDefinitions = [
            ['key' => 'id', 'label' => __('ID')],
            ['key' => 'name', 'label' => __('Name')],
            ['key' => 'last_name_1', 'label' => __('First last name')],
            ['key' => 'last_name_2', 'label' => __('Second last name')],
            ['key' => 'document_number', 'label' => __('Document number')],
            ['key' => 'lang', 'label' => __('Language'), 'transformer' => fn ($p) => $p->lang ? strtoupper($p->lang) : ''],
            ['key' => 'entity', 'label' => __('Entity'), 'transformer' => fn ($p) => $p->entity->name ?? ''],
            ['key' => 'personTypes', 'label' => __('Person types'), 'transformer' => fn ($p) => $p->personTypes->pluck('name_eu')->implode(', ')],
            ['key' => 'active', 'label' => __('Status'), 'transformer' => fn ($p) => $p->active ? __('basics13::messages.active_label') : __('basics13::messages.inactive_label')],
            ['key' => 'created_at', 'label' => __('Created at')],
            ['key' => 'updated_at', 'label' => __('Updated at')],
            ['key' => 'notes', 'label' => __('Notes')],
        ];

        return (new ExcelExportService(
            query: $queryBuilder,
            title: 'Persons Export',
            columnDefinitions: $columnDefinitions,
            maxRows: 50000,
        ))->download(ExcelExportService::fileName(__('Persons'), $scope));
    }
}
