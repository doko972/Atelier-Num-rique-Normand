<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Municipality;
use Illuminate\Database\Seeder;

/**
 * Territoire d'intervention : communes du secteur.
 *
 * Les communes sont des données publiques ; les distances sont approximatives
 * et se règlent depuis « Territoire → Communes ».
 *
 * Ce seeder ne crée volontairement ni lieu d'accueil, ni partenaire : ces
 * enregistrements désignent des structures réelles. En inventer conduirait à
 * publier des adresses inexistantes et à présenter comme partenaires des
 * organismes qui ne le sont pas. Ils se saisissent depuis le back-office,
 * une fois les accords obtenus.
 */
class DirectorySeeder extends Seeder
{
    public function run(): void
    {
        $municipalities = [
            ['name' => 'Condé-en-Normandie', 'postal_code' => '14110', 'department' => 'Calvados', 'distance_km' => 0],
            ['name' => 'Pontécoulant', 'postal_code' => '14110', 'department' => 'Calvados', 'distance_km' => 6],
            ['name' => 'Valdallière', 'postal_code' => '14410', 'department' => 'Calvados', 'distance_km' => 8],
            ['name' => 'Pont-d’Ouilly', 'postal_code' => '14690', 'department' => 'Calvados', 'distance_km' => 13],
            ['name' => 'Flers', 'postal_code' => '61100', 'department' => 'Orne', 'distance_km' => 13],
            ['name' => 'Athis-Val-de-Rouvre', 'postal_code' => '61430', 'department' => 'Orne', 'distance_km' => 16],
            ['name' => 'Clécy', 'postal_code' => '14570', 'department' => 'Calvados', 'distance_km' => 17],
            ['name' => 'Vire Normandie', 'postal_code' => '14500', 'department' => 'Calvados', 'distance_km' => 20],
            ['name' => 'Les Monts d’Aunay', 'postal_code' => '14260', 'department' => 'Calvados', 'distance_km' => 22],
            ['name' => 'Thury-Harcourt-le-Hom', 'postal_code' => '14220', 'department' => 'Calvados', 'distance_km' => 25],
        ];

        foreach ($municipalities as $position => $municipality) {
            Municipality::updateOrCreate(
                ['name' => $municipality['name'], 'postal_code' => $municipality['postal_code']],
                [
                    ...$municipality,
                    'is_covered' => true,
                    'home_visits_available' => true,
                    'position' => $position,
                ],
            );
        }
    }
}
