<?php

namespace entities13\Http\Requests;

use entities13\Models\Entity;
use Illuminate\Validation\Rule;
use entities13\Models\EntityType;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rules\Exists;
use Basics13\Support\Validation\MaxLength;
use Illuminate\Foundation\Http\FormRequest;

class EntityRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $name = $this->input('name');

        if (is_string($name)) {
            $this->merge(['name' => trim($name)]);
        }
    }

    public function authorize(): bool
    {
        $entity = $this->route('entity');

        return $entity
            ? ($this->user()?->can('update', $entity) ?? false)
            : ($this->user()?->can('create', Entity::class) ?? false);
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
                'min:4',
                'max:'.MaxLength::string(),
                Rule::unique(Entity::class)
                    ->ignore($this->route('entity'))
                    ->whereNull('deleted_at'),
            ],
            'notes' => ['nullable', 'string', 'max:'.MaxLength::longText()],
            'entity_type_ids' => ['sometimes', 'array'],
            'entity_type_ids.*' => ['integer', 'distinct', $this->selectableTypeRule()],
            'reuse_deleted_name' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * The types the form offers are the active ones. A type the entity already holds stays valid
     * after it has been archived, so a form that does not change it can still be saved without
     * silently dropping the type from a entity nobody meant to touch.
     */
    private function selectableTypeRule(): Exists
    {
        $entity = $this->route('entity');
        $assigned = $entity instanceof Entity
            ? $entity->entityTypes()->pluck('entity_type_id')->all()
            : [];

        return Rule::exists(EntityType::class, 'id')
            ->whereNull('deleted_at')
            ->where(static function (Builder $query) use ($assigned): void {
                $query->where('active', true);

                if ($assigned !== []) {
                    $query->orWhereIn('id', $assigned);
                }
            });
    }
}
