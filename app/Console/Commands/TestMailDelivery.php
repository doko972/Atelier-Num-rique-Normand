<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Vérifie que les courriels partent réellement.
 *
 * Un formulaire public annonce « bien envoyé » dès que la notification est
 * acceptée, pas quand elle arrive : une erreur SMTP ou une file d'attente
 * sans exécutant reste donc invisible côté visiteur. Cette commande rend la
 * panne visible.
 *
 * Elle envoie de façon synchrone, en contournant la file : on veut savoir si
 * le serveur SMTP répond, pas si un travail a été mis en file.
 */
class TestMailDelivery extends Command
{
    protected $signature = 'courriel:tester {destinataire? : Adresse de test (par défaut, celle des notifications d’administration)}';

    protected $description = 'Envoie un courriel de test et affiche la configuration réellement utilisée.';

    public function handle(): int
    {
        $this->showConfiguration();
        $this->warnAboutQueue();

        $recipient = $this->argument('destinataire')
            ?? config('site.contact.admin_email');

        if (! is_string($recipient) || $recipient === '') {
            $this->components->error('Aucun destinataire : passez une adresse en argument.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->components->task(
            "Envoi vers {$recipient}",
            fn (): bool => $this->send($recipient),
        );

        return $this->failed ? self::FAILURE : self::SUCCESS;
    }

    private bool $failed = false;

    private function send(string $recipient): bool
    {
        try {
            Mail::raw(
                "Ceci est un courriel de test.\n\n".
                "S'il vous parvient, la configuration SMTP du site est correcte.\n".
                'Envoyé depuis '.config('app.url'),
                fn ($message) => $message->to($recipient)->subject('Test d’envoi — '.config('app.name')),
            );

            return true;
        } catch (Throwable $exception) {
            $this->failed = true;

            $this->newLine();
            $this->components->error($exception->getMessage());
            $this->hint($exception->getMessage());

            return false;
        }
    }

    private function showConfiguration(): void
    {
        $mailer = (string) config('mail.default');

        $this->components->info('Configuration utilisée');
        $this->components->twoColumnDetail('Transport', $mailer);

        if ($mailer === 'smtp') {
            $port = (int) config('mail.mailers.smtp.port');
            $scheme = config('mail.mailers.smtp.scheme');

            $this->components->twoColumnDetail('Serveur', (string) config('mail.mailers.smtp.host'));
            $this->components->twoColumnDetail('Port', (string) $port);
            $this->components->twoColumnDetail(
                'Chiffrement (MAIL_SCHEME)',
                is_string($scheme) && $scheme !== '' ? $scheme : '<fg=yellow>non défini</>',
            );
            $this->components->twoColumnDetail('Identifiant', (string) config('mail.mailers.smtp.username'));

            // Aucune alerte ici : Symfony déduit le chiffrement implicite du
            // port 465 lorsque MAIL_SCHEME n'est pas défini. Avertir sur la
            // seule lecture de la configuration produirait un faux positif
            // sur une installation qui fonctionne. Le conseil n'est donné
            // qu'en cas d'échec réel, dans hint().
        }

        $this->components->twoColumnDetail('Expéditeur', (string) config('mail.from.address'));
        $this->components->twoColumnDetail('Notifications vers', (string) config('site.contact.admin_email'));
    }

    /**
     * Les notifications du site passent par la file d'attente.
     *
     * Sur un hébergement mutualisé, aucun exécutant ne tourne en permanence :
     * une file « database » retient les courriels indéfiniment, alors que les
     * formulaires annoncent un envoi réussi.
     */
    private function warnAboutQueue(): void
    {
        $connection = (string) config('queue.default');

        $this->newLine();
        $this->components->info('File d’attente');
        $this->components->twoColumnDetail('QUEUE_CONNECTION', $connection);

        if ($connection === 'sync') {
            return;
        }

        $this->components->warn(
            'Les notifications du site sont mises en file. Sans exécutant '.
            '(`queue:work`), elles ne partiront jamais — alors que les '.
            'formulaires afficheront « bien envoyé ».',
        );

        if ($connection !== 'database') {
            return;
        }

        try {
            $pending = DB::table('jobs')->count();
        } catch (Throwable) {
            return;
        }

        $this->components->twoColumnDetail(
            'Courriels en attente',
            $pending > 0 ? "<fg=red>{$pending}</>" : '0',
        );

        if ($pending > 0) {
            $this->line('  Pour les envoyer : php artisan queue:work --stop-when-empty');
        }
    }

    /**
     * Traduit les erreurs SMTP les plus fréquentes en action à mener.
     */
    private function hint(string $message): void
    {
        $hint = match (true) {
            str_contains($message, 'Connection could not be established'),
            str_contains($message, 'Connection timed out') => 'Port ou chiffrement incorrect. Essayez le port 465 avec MAIL_SCHEME=smtps, ou le port 587 sans MAIL_SCHEME.',
            str_contains($message, 'Authentication failed'),
            str_contains($message, '535') => 'Identifiant ou mot de passe refusé. Vérifiez que la boîte existe et que le mot de passe est à jour.',
            str_contains($message, 'Sender address rejected'),
            str_contains($message, '553') => 'MAIL_FROM_ADDRESS doit correspondre à une boîte du domaine, et à MAIL_USERNAME.',
            default => null,
        };

        if ($hint !== null) {
            $this->line('  '.$hint);
        }
    }
}
