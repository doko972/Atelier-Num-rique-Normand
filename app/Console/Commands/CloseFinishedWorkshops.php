<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\WorkshopStatus;
use App\Models\Workshop;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Bascule en « terminé » les ateliers dont la date est passée.
 *
 * Sans cela, l'agenda public continuerait d'afficher des ateliers révolus, et
 * les statistiques de remplissage resteraient fausses.
 */
class CloseFinishedWorkshops extends Command
{
    protected $signature = 'ateliers:cloturer';

    protected $description = 'Passe en « terminé » les ateliers dont la date est dépassée.';

    public function handle(): int
    {
        $workshops = Workshop::query()
            ->whereIn('status', [WorkshopStatus::Published, WorkshopStatus::Full])
            ->whereDate('date', '<', today())
            ->get();

        foreach ($workshops as $workshop) {
            $workshop->update(['status' => WorkshopStatus::Finished]);
        }

        $count = $workshops->count();

        $this->info("{$count} atelier(s) clôturé(s).");

        if ($count > 0) {
            Log::channel('ateliers')->info('Ateliers clôturés automatiquement.', ['count' => $count]);
        }

        return self::SUCCESS;
    }
}
