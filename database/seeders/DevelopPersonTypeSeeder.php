<?php

namespace Entities13\Database\Seeders;

use Illuminate\Database\Seeder;
use Entities13\Models\PersonType;

/**
 * Example person types plus the requested number of bulk types for a development database.
 *
 * The four names are filled in the language each column belongs to, so the four columns of the type
 * list do not all read the same, and each language keeps its own value unique among the rows that
 * are not in the trash.
 */
class DevelopPersonTypeSeeder extends Seeder
{
    private const STATE_WEIGHTS = ['active' => 12, 'archived' => 5, 'trashed' => 3];

    public function run(int $personTypes = 20): void
    {
        PersonType::factory()->create([
            'name_eu' => 'Pertsona mota euskaraz bakarrik',
            'name_es' => null,
            'name_fr' => null,
            'name_en' => null,
        ]);

        PersonType::factory()->create([
            'name_eu' => 'Pertsona estrategikoa',
            'name_es' => 'Persona estratégica',
            'name_fr' => 'Personne stratégique',
            'name_en' => 'Strategic person',
        ]);

        $held = PersonType::factory()->create([
            'name_eu' => 'Pertsona mota erabilia',
            'name_es' => 'Tipo de persona en uso',
            'name_fr' => 'Type de personne utilisé',
            'name_en' => 'Person type in use',
        ]);

        $archivedHeld = PersonType::factory()->archived()->create([
            'name_eu' => 'Pertsona mota zaharkitua erabilia',
            'name_es' => 'Tipo de persona archivado en uso',
            'name_fr' => 'Type de personne archivé utilisé',
            'name_en' => 'Archived person type in use',
        ]);

        PersonType::factory()->trashed()->create([
            'name_eu' => 'Pertsona mota zakarrontzian',
            'name_es' => 'Tipo de persona en la papelera',
            'name_fr' => 'Type de personne à la corbeille',
            'name_en' => 'Person type in the trash',
        ]);

        $states = [];

        foreach (self::STATE_WEIGHTS as $state => $weight) {
            array_push($states, ...array_fill(0, $weight, $state));
        }

        for ($number = 1; $number <= $personTypes; $number++) {
            $factory = match ($states[($number - 1) % count($states)]) {
                'archived' => PersonType::factory()->archived(),
                'trashed' => PersonType::factory()->trashed(),
                default => PersonType::factory(),
            };

            $factory->createOne([
                'name_eu' => sprintf('Perf Pertsona Mota %05d', $number),
                'name_es' => sprintf('Perf Tipo Persona %05d', $number),
                'name_fr' => sprintf('Perf Type Personne %05d', $number),
                'name_en' => sprintf('Perf Person Type %05d', $number),
            ]);
        }
    }
}
