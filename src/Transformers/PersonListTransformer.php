<?php

namespace Entities13\Transformers;

use Entities13\Models\Person;
use Illuminate\Database\Eloquent\Model;
use Basics13\Transformers\ListTransformer;

/**
 * @extends ListTransformer<Person>
 */
class PersonListTransformer extends ListTransformer
{
    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    protected function columns(Model $record, array $extra): array
    {
        /** @var Person $record */
        return [
            'id' => $record->id,
            'name' => $record->name,
            'last_name_1' => $record->last_name_1,
            'last_name_2' => $record->last_name_2,
            'document_number' => $record->document_number,
            'lang' => $record->lang,
            'entity' => $record->entity ? [
                'id' => $record->entity->id,
                'name' => $record->entity->name,
            ] : null,
            'personTypes' => $record->personTypes->map(fn ($type) => [
                'id' => $type->id,
                'name' => $type->name_eu,
                'active' => $type->active,
            ])->all(),
            'editPayload' => $this->editPayload($record),
            ...$extra,
        ];
    }

    protected function getDeleteBlockCondition(Model $record): bool
    {
        return false;
    }

    protected function getForceDeleteBlockCondition(Model $record): bool
    {
        return false;
    }

    protected function getForceDeleteBlockHint(Model $record): string
    {
        return '';
    }

    /**
     * @param  Person  $record
     * @return array{id: int, name: string, last_name_1: string|null, last_name_2: string|null, document_number: string|null, lang: string|null, entity_id: int|null, person_type_ids: list<string>, notes: string|null}
     */
    protected function editPayload(Model $record): array
    {
        return [
            ...$record->only(['id', 'name', 'last_name_1', 'last_name_2', 'document_number', 'lang', 'entity_id', 'notes']),
            'person_type_ids' => array_values($record->personTypes
                ->pluck('id')
                ->map(static fn (int $id): string => (string) $id)
                ->all()),
        ];
    }

    /**
     * The person's full name is composed from name, last_name_1, last_name_2.
     *
     * @param  Person  $record
     */
    protected function recordName(Model $record): string
    {
        return $record->getFullName();
    }

    protected function resourceLabel(): string
    {
        return __('Persons');
    }

    protected function resourceKey(): string
    {
        return 'person';
    }

    /**
     * The routes are named `persons.*`, while the plural convention would give `people.*`.
     *
     * @return array{active: string, archived: string, trash: string, destroy: string, archive: string, activate: string, restore: string, trashDestroy: string}
     */
    protected function routes(): array
    {
        return [
            'active' => 'persons.index',
            'archived' => 'persons.archived.index',
            'trash' => 'persons.trash.index',
            'destroy' => 'persons.destroy',
            'archive' => 'persons.archive',
            'activate' => 'persons.archived.activate',
            'restore' => 'persons.trash.restore',
            'trashDestroy' => 'persons.trash.destroy',
        ];
    }
}
