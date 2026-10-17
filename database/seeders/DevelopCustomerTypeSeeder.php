<?php

namespace Customers13\Database\Seeders;

use Illuminate\Database\Seeder;
use Customers13\Models\Customer;
use Customers13\Models\CustomerType;

/**
 * Example customer types plus the requested number of bulk types for a development database.
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
        CustomerType::factory()->create([
            'name_eu' => 'Bezero mota euskaraz bakarrik',
            'name_es' => null,
            'name_fr' => null,
            'name_en' => null,
        ]);

        CustomerType::factory()->create([
            'name_eu' => 'Bezero estrategikoa',
            'name_es' => 'Cliente estratégico',
            'name_fr' => 'Client stratégique',
            'name_en' => 'Strategic customer',
        ]);

        $held = CustomerType::factory()->create([
            'name_eu' => 'Bezero mota erabilia',
            'name_es' => 'Tipo de cliente en uso',
            'name_fr' => 'Type de client utilisé',
            'name_en' => 'Customer type in use',
        ]);

        $archivedHeld = CustomerType::factory()->archived()->create([
            'name_eu' => 'Bezero mota zaharkitua erabilia',
            'name_es' => 'Tipo de cliente archivado en uso',
            'name_fr' => 'Type de client archivé utilisé',
            'name_en' => 'Archived customer type in use',
        ]);

        Customer::factory()
            ->create(['name' => 'Customer holding customer types 0'])
            ->customerTypes()
            ->attach([$held->id, $archivedHeld->id]);

        CustomerType::factory()->trashed()->create([
            'name_eu' => 'Bezero mota zakarrontzian',
            'name_es' => 'Tipo de cliente en la papelera',
            'name_fr' => 'Type de client à la corbeille',
            'name_en' => 'Customer type in the trash',
        ]);

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
