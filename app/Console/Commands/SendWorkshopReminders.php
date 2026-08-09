<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\RegistrationStatus;
use App\Enums\WorkshopStatus;
use App\Models\Workshop;
use App\Notifications\WorkshopReminderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Rappel envoyé quelques jours avant chaque atelier (codex §10).
 *
 * Les personnes sans adresse électronique ne reçoivent rien : la commande les
 * liste en fin d'exécution pour que le conseiller les appelle.
 */
class SendWorkshopReminders extends Command
{
    protected $signature = 'ateliers:rappels
                            {--jours= : Nombre de jours avant l’atelier (défaut : configuration du site)}
                            {--simulation : Affiche ce qui serait envoyé, sans rien envoyer}';

    protected $description = 'Envoie les rappels aux personnes inscrites aux ateliers à venir.';

    public function handle(): int
    {
        $days = (int) ($this->option('jours') ?? config('site.workshops.reminder_days_before'));
        $simulation = (bool) $this->option('simulation');
        $targetDate = now()->addDays($days)->toDateString();

        $workshops = Workshop::query()
            ->whereIn('status', [WorkshopStatus::Published, WorkshopStatus::Full])
            ->whereDate('date', $targetDate)
            ->with('location')
            ->get();

        if ($workshops->isEmpty()) {
            $this->info("Aucun atelier prévu le {$targetDate}. Rien à envoyer.");

            return self::SUCCESS;
        }

        $sent = 0;
        $toCall = [];

        foreach ($workshops as $workshop) {
            $registrations = $workshop->registrations()
                ->whereIn('status', [RegistrationStatus::Pending, RegistrationStatus::Confirmed])
                ->whereNull('reminder_sent_at')
                ->get();

            foreach ($registrations as $registration) {
                if (! $registration->canReceiveEmail()) {
                    $toCall[] = sprintf(
                        '%s — %s (%s)',
                        $workshop->title,
                        $registration->fullName(),
                        $registration->phone,
                    );

                    continue;
                }

                if ($simulation) {
                    $this->line("Rappel prévu pour {$registration->reference}.");
                    $sent++;

                    continue;
                }

                Notification::route('mail', $registration->email)
                    ->notify(new WorkshopReminderNotification($registration));

                $registration->forceFill(['reminder_sent_at' => now()])->save();
                $sent++;
            }
        }

        $this->info("{$sent} rappel(s) ".($simulation ? 'seraient envoyés' : 'envoyés').'.');

        if ($toCall !== []) {
            $this->newLine();
            $this->warn('Personnes sans adresse électronique, à appeler :');

            foreach ($toCall as $line) {
                $this->line('  • '.$line);
            }
        }

        Log::channel('ateliers')->info('Rappels d’atelier traités.', [
            'date' => $targetDate,
            'sent' => $sent,
            'to_call' => count($toCall),
            'simulation' => $simulation,
        ]);

        return self::SUCCESS;
    }
}
