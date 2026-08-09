<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AuditLog;
use Illuminate\Database\Seeder;

/**
 * Amorçage de l'application.
 *
 * Ce seeder ne pose que ce qui est vrai : rôles, paramètres, pages légales,
 * communes, catalogue de services et ressources. Il est sûr à exécuter en
 * production, et chaque seeder est idempotent.
 *
 * Les données inventées — ateliers d'exemple, comptes de démonstration,
 * témoignages — vivent dans {@see DemoSeeder}, appelé séparément :
 *
 *     php artisan db:seed --class=DemoSeeder
 *
 * Les séparer évite qu'un atelier fictif se retrouve publié sur un site réel,
 * avec des personnes se présentant à une date qui n'existe pas.
 *
 * Les événements de modèle restent actifs — la génération des identifiants
 * d'URL en dépend — mais la journalisation d'audit est suspendue : amorcer la
 * base n'est pas une action d'administration.
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        AuditLog::withoutRecording(function (): void {
            $this->call([
                RoleSeeder::class,
                SettingSeeder::class,
                PageSeeder::class,
                DirectorySeeder::class,
                ServiceSeeder::class,
                ResourceSeeder::class,
                AiLiteracySeeder::class,
            ]);
        });
    }
}
