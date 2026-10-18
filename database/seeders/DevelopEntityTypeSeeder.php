<?php

namespace Entities13\Database\Seeders;

use entities13\Models\Entity;
use Illuminate\Database\Seeder;
use entities13\Models\EntityType;

/**
 * Example entity types plus the requested number of bulk types for a development database.
 *
 * The four names are filled in the language each column belongs to, so the four columns of the type
 * list do not all read the same, and each language keeps its own value unique among the rows that
 * are not in the trash.
 */
class DevelopEntityTypeSeeder extends Seeder
{
    private const STATE_WEIGHTS = ['active' => 12, 'archived' => 5, 'trashed' => 3];

    public function run(int $entityTypes = 20): void
    {
        EntityType::factory()->create([
            'name_eu' => 'Bezero mota euskaraz bakarrik',
            'name_es' => null,
            'name_fr' => null,
            'name_en' => null,
        ]);

        EntityType::factory()->create([
            'name_eu' => 'Bezero estrategikoa',
            'name_es' => 'Cliente estratégico',
            'name_fr' => 'Client stratégique',
            'name_en' => 'Strategic entity',
        ]);

        $held = EntityType::factory()->create([
            'name_eu' => 'Bezero mota erabilia',
            'name_es' => 'Tipo de cliente en uso',
            'name_fr' => 'Type de client utilisé',
            'name_en' => 'Entity type in use',
        ]);

        $archivedHeld = EntityType::factory()->archived()->create([
            'name_eu' => 'Bezero mota zaharkitua erabilia',
            'name_es' => 'Tipo de cliente archivado en uso',
            'name_fr' => 'Type de client archivé utilisé',
            'name_en' => 'Archived entity type in use',
        ]);

        Entity::factory()
            ->create(['name' => 'Entity holding entity types 0'])
            ->entityTypes()
            ->attach([$held->id, $archivedHeld->id]);

        EntityType::factory()->trashed()->create([
            'name_eu' => 'Bezero mota zakarrontzian',
            'name_es' => 'Tipo de cliente en la papelera',
            'name_fr' => 'Type de client à la corbeille',
            'name_en' => 'Entity type in the trash',
        ]);

        $states = [];

        foreach (self::STATE_WEIGHTS as $state => $weight) {
            array_push($states, ...array_fill(0, $weight, $state));
        }

        for ($number = 1; $number <= $entityTypes; $number++) {
            $factory = match ($states[($number - 1) % count($states)]) {
                'archived' => EntityType::factory()->archived(),
                'trashed' => EntityType::factory()->trashed(),
                default => EntityType::factory(),
            };

            $factory->createOne([
                'name_eu' => sprintf('Perf Bezero Mota %05d', $number),
                'name_es' => sprintf('Perf Tipo Cliente %05d', $number),
                'name_fr' => sprintf('Perf Type Client %05d', $number),
                'name_en' => sprintf('Perf Entity Type %05d', $number),
            ]);
        }
    }
}
