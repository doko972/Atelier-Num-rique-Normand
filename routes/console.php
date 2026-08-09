<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Tâches planifiées
|--------------------------------------------------------------------------
|
| Un seul appel cron est nécessaire en production :
|   * * * * * cd /var/www/conseillernumerique && php artisan schedule:run >> /dev/null 2>&1
|
*/

// Rappel des ateliers : le matin, à une heure où un courriel est lu.
Schedule::command('ateliers:rappels')
    ->dailyAt('08:30')
    ->timezone('Europe/Paris')
    ->onOneServer()
    ->withoutOverlapping();

// Clôture des ateliers passés, juste après minuit.
Schedule::command('ateliers:cloturer')
    ->dailyAt('00:15')
    ->timezone('Europe/Paris')
    ->onOneServer();

// Purge RGPD : la nuit, hors des heures d'usage du back-office.
Schedule::command('rgpd:purger')
    ->weeklyOn(1, '03:00')
    ->timezone('Europe/Paris')
    ->onOneServer()
    ->withoutOverlapping();

// Nettoyage des jetons de réinitialisation expirés.
Schedule::command('auth:clear-resets')
    ->daily()
    ->onOneServer();

// Suppression des tâches en échec conservées plus d'un mois.
Schedule::command('queue:prune-failed --hours=720')
    ->weekly()
    ->onOneServer();
