<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AuditLog;
use Illuminate\Database\Seeder;

/**
 * Données de démonstration.
 *
 * Volontairement séparé de {@see DatabaseSeeder} : tout ce qu'il crée est
 * inventé — comptes, ateliers, personnes inscrites. Sur un site en service,
 * un atelier fictif publié ferait se déplacer quelqu'un pour rien.
 *
 *     php artisan db:seed --class=DemoSeeder
 *
 * À n'exécuter qu'en local ou sur un environnement de recette.
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            $this->command?->warn('DemoSeeder ignoré : données inventées interdites en production.');

            return;
        }

        AuditLog::withoutRecording(function (): void {
            $this->call([
                UserSeeder::class,
                WorkshopSeeder::class,
            ]);
        });
    }
}
