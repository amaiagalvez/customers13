<?php

namespace Customers13\Database\Factories;

use Customers13\Models\Customer;
use Basics13\Database\Factories\HasStates;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Attributes\UseModel;

/**
 * @extends Factory<Customer>
 */
#[UseModel(Customer::class)]
class CustomerFactory extends Factory
{
    use HasStates;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->name(),
        ];
    }
}
