<?php

namespace Customers13\Database\Seeders;

use Illuminate\Database\Seeder;
use Customers13\Models\Customer;
use Customers13\Models\CustomerType;

/**
 * The customer type cases a demo database should show that the bulk seeder does not:
 *
 * - a type named in euskera only, since the other three languages are optional;
 * - a type named in the four languages;
 * - a type a customer holds, which the active list offers to archive instead of deleting;
 * - an archived type a customer still holds, which stays undeletable;
 * - a trashed type, whose euskera name is free again for the conflict the create form resolves.
 *
 * The customer holding the types is created here, the same way the project edge cases bring the
 * parent record they need.
 */
class EdgeCaseCustomerTypeSeeder extends Seeder
{
    public function run(): void
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
    }
}
