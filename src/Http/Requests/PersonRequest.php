<?php

namespace Entities13\Http\Requests;

use Entities13\Models\Entity;
use Entities13\Models\Person;
use Illuminate\Validation\Rule;
use Entities13\Models\PersonType;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rules\Exists;
use Basics13\Support\Validation\MaxLength;
use Illuminate\Foundation\Http\FormRequest;

class PersonRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $name = $this->input('name');

        if (is_string($name)) {
            $this->merge(['name' => trim($name)]);
        }

        foreach (['last_name_1', 'last_name_2', 'document_number'] as $field) {
            $value = $this->input($field);

            if (is_string($value)) {
                $this->merge([$field => trim($value) === '' ? null : trim($value)]);
            }
        }
    }

    public function authorize(): bool
    {
        $person = $this->route('person');

        return $person
            ? ($this->user()?->can('update', $person) ?? false)
            : ($this->user()?->can('create', Person::class) ?? false);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'min:2',
                'max:'.MaxLength::string(),
            ],
            'last_name_1' => ['nullable', 'string', 'max:'.MaxLength::string()],
            'last_name_2' => ['nullable', 'string', 'max:'.MaxLength::string()],
            'document_number' => ['nullable', 'string', 'max:'.MaxLength::string()],
            'lang' => ['nullable', 'string', 'size:2', Rule::in(['eu', 'es', 'fr', 'en'])],
            'entity_id' => ['sometimes', 'nullable', 'integer', $this->selectableEntityRule()],
            'person_type_ids' => ['sometimes', 'array'],
            'person_type_ids.*' => ['integer', 'distinct', $this->selectableTypeRule()],
            'notes' => ['nullable', 'string', 'max:'.MaxLength::longText()],
        ];
    }

    /**
     * The entity the form offers is the active one. An entity the person already has stays valid
     * after it has been archived, so a form that does not change it can still be saved without
     * silently dropping the entity from a person nobody meant to touch.
     */
    private function selectableEntityRule(): Exists
    {
        $person = $this->route('person');
        $assigned = $person instanceof Person && $person->entity_id !== null
            ? [$person->entity_id]
            : [];

        $rule = Rule::exists(Entity::class, 'id')
            ->whereNull('deleted_at')
            ->where(static function (Builder $query) use ($assigned): void {
                $query->where('active', true);

                if ($assigned !== []) {
                    $query->orWhereIn('id', $assigned);
                }
            });

        // Authorization: user must have entities.view to select an entity
        if (! $this->user()?->can('viewAny', Entity::class)) {
            return $rule->where(function (Builder $query): void {
                $query->where('id', -1); // Impossible condition
            });
        }

        return $rule;
    }

    /**
     * The types the form offers are the active ones. A type the person already holds stays valid
     * after it has been archived, so a form that does not change it can still be saved without
     * silently dropping the type from a person nobody meant to touch.
     */
    private function selectableTypeRule(): Exists
    {
        $person = $this->route('person');
        $assigned = $person instanceof Person
            ? $person->personTypes()->pluck('person_type_id')->all()
            : [];

        return Rule::exists(PersonType::class, 'id')
            ->whereNull('deleted_at')
            ->where(static function (Builder $query) use ($assigned): void {
                $query->where('active', true);

                if ($assigned !== []) {
                    $query->orWhereIn('id', $assigned);
                }
            });
    }
}
