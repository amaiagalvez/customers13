<?php

namespace entities13\Database\Seeders;

use Illuminate\Database\Seeder;
use entities13\Models\Entity;

class DevelopEntitySeeder extends Seeder
{
    private const STATE_WEIGHTS = ['active' => 12, 'archived' => 5, 'trashed' => 3];

    public function run(int $entities = 100): void
    {
        $states = [];

        foreach (self::STATE_WEIGHTS as $state => $weight) {
            array_push($states, ...array_fill(0, $weight, $state));
        }

        for ($number = 1; $number <= $entities; $number++) {
            $state = $states[($number - 1) % count($states)];
            $factory = match ($state) {
                'archived' => Entity::factory()->archived(),
                'trashed' => Entity::factory()->trashed(),
                default => Entity::factory(),
            };

            $factory->createOne([
                'name' => sprintf('Perf Entity %05d', $number),
            ]);
        }
    }
}
