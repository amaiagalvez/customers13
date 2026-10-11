<?php

namespace Customers13\Database\Seeders;

use Illuminate\Database\Seeder;
use Customers13\Models\Customer;

class DevelopCustomerSeeder extends Seeder
{
    private const STATE_WEIGHTS = ['active' => 12, 'archived' => 5, 'trashed' => 3];

    public function run(int $customers = 100): void
    {
        $states = [];

        foreach (self::STATE_WEIGHTS as $state => $weight) {
            array_push($states, ...array_fill(0, $weight, $state));
        }

        for ($number = 1; $number <= $customers; $number++) {
            $state = $states[($number - 1) % count($states)];
            $factory = match ($state) {
                'archived' => Customer::factory()->archived(),
                'trashed' => Customer::factory()->trashed(),
                default => Customer::factory(),
            };

            $factory->createOne([
                'name' => sprintf('Perf Customer %05d', $number),
            ]);
        }
    }
}
