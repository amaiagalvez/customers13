<?php

namespace Customers13\Database\Seeders;

use Illuminate\Database\Seeder;
use Customers13\Models\CustomerType;

/**
 * Bulk customer types for a development database.
 *
 * The four names are filled in the language each column belongs to, so the four columns of the type
 * list do not all read the same, and each language keeps its own value unique among the rows that
 * are not in the trash.
 */
class DevelopCustomerTypeSeeder extends Seeder
{
    private const STATE_WEIGHTS = ['active' => 12, 'archived' => 5, 'trashed' => 3];

    public function run(int $customerTypes = 20): void
    {
        $states = [];

        foreach (self::STATE_WEIGHTS as $state => $weight) {
            array_push($states, ...array_fill(0, $weight, $state));
        }

        for ($number = 1; $number <= $customerTypes; $number++) {
            $factory = match ($states[($number - 1) % count($states)]) {
                'archived' => CustomerType::factory()->archived(),
                'trashed' => CustomerType::factory()->trashed(),
                default => CustomerType::factory(),
            };

            $factory->createOne([
                'name_eu' => sprintf('Perf Bezero Mota %05d', $number),
                'name_es' => sprintf('Perf Tipo Cliente %05d', $number),
                'name_fr' => sprintf('Perf Type Client %05d', $number),
                'name_en' => sprintf('Perf Customer Type %05d', $number),
            ]);
        }
    }
}
