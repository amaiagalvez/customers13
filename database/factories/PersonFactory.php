<?php

namespace Entities13\Database\Factories;

use Entities13\Models\Entity;
use Entities13\Models\Person;
use Entities13\Models\PersonType;
use Basics13\Database\Factories\HasStates;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Person>
 */
class PersonFactory extends Factory
{
    use HasStates;

    protected $model = Person::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->firstName(),
            'last_name_1' => fake()->lastName(),
            'last_name_2' => fake()->lastName(),
            'document_number' => fake()->optional()->bothify('??#######'),
            'lang' => fake()->randomElement(['eu', 'es', 'fr', 'en']),
        ];
    }

    /**
     * State for a person linked to an entity.
     */
    public function forEntity(Entity $entity): static
    {
        return $this->state(fn (array $attributes) => [
            'entity_id' => $entity->id,
        ]);
    }

    /**
     * State for a person with specific person types.
     *
     * @param  Collection<int, PersonType>|array<int, PersonType>  $types
     */
    public function withTypes(Collection|array $types): static
    {
        return $this->afterCreating(function (Person $person) use ($types): void {
            $person->personTypes()->sync($types);
        });
    }

    /**
     * State for a person with specific person types.
     *
     * @param  Collection<int, PersonType>|array<int, PersonType>  $types
     */
    public function hasTypes(Collection|array $types): static
    {
        return $this->state(fn (array $attributes) => [
            'person_type_ids' => implode(',', $types instanceof Collection ? $types->pluck('id')->toArray() : $types),
        ]);
    }
}
