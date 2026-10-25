<?php

namespace Entities13\Database\Factories;

use Entities13\Models\PersonType;
use Basics13\Database\Factories\HasStates;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PersonType>
 */
class PersonTypeFactory extends Factory
{
    use HasStates;

    protected $model = PersonType::class;

    /**
     * Define the model's default state.
     *
     * Every language is filled with a value no other record of this factory has used, so a test can
     * count on the four unique active indexes not getting in each other's way.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name_eu' => fake()->unique()->words(2, true),
            'name_es' => fake()->unique()->words(2, true),
            'name_fr' => fake()->unique()->words(2, true),
            'name_en' => fake()->unique()->words(2, true),
        ];
    }
}
