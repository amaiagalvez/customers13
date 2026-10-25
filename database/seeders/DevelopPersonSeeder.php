<?php

namespace Entities13\Database\Seeders;

use Entities13\Models\Entity;
use Entities13\Models\Person;
use Illuminate\Database\Seeder;
use Entities13\Models\PersonType;

/**
 * Example persons linked to existing entities for a development database.
 */
class DevelopPersonSeeder extends Seeder
{
    private const STATE_WEIGHTS = ['active' => 12, 'archived' => 5, 'trashed' => 3];

    public function run(int $persons = 30): void
    {
        $entities = Entity::query()->where('active', true)->get();
        $personTypes = PersonType::query()->where('active', true)->get();

        if ($entities->isEmpty() || $personTypes->isEmpty()) {
            return;
        }

        // Create a few specific persons with known data
        Person::factory()->forEntity($entities->first())->withTypes($personTypes->take(2))->create([
            'name' => 'Ander',
            'last_name_1' => 'García',
            'last_name_2' => 'López',
            'document_number' => '12345678A',
            'lang' => 'eu',
        ]);

        Person::factory()->forEntity($entities->first())->withTypes($personTypes->take(1))->archived()->create([
            'name' => 'Maria',
            'last_name_1' => 'Rodríguez',
            'last_name_2' => 'Martínez',
            'document_number' => '87654321B',
            'lang' => 'es',
        ]);

        Person::factory()->forEntity($entities->first())->withTypes($personTypes->take(1))->trashed()->create([
            'name' => 'Jon',
            'last_name_1' => 'Fernández',
            'last_name_2' => 'González',
            'document_number' => '11223344C',
            'lang' => 'eu',
        ]);

        // Create a person without entity
        Person::factory()->create([
            'name' => 'Leire',
            'last_name_1' => 'Sánchez',
            'last_name_2' => 'Pérez',
            'document_number' => '99887766D',
            'lang' => 'eu',
        ]);

        $states = [];

        foreach (self::STATE_WEIGHTS as $state => $weight) {
            array_push($states, ...array_fill(0, $weight, $state));
        }

        for ($number = 1; $number <= $persons; $number++) {
            $entity = $entities->random();
            $types = $personTypes->random(rand(1, min(3, $personTypes->count())));

            $factory = match ($states[($number - 1) % count($states)]) {
                'archived' => Person::factory()->archived(),
                'trashed' => Person::factory()->trashed(),
                default => Person::factory(),
            };

            $factory->forEntity($entity)->withTypes($types)->createOne([
                'name' => fake()->firstName(),
                'last_name_1' => fake()->lastName(),
                'last_name_2' => fake()->lastName(),
                'document_number' => fake()->optional()->bothify('??#######'),
                'lang' => fake()->randomElement(['eu', 'es', 'fr', 'en']),
            ]);
        }
    }
}
