<?php

declare(strict_types=1);

namespace App\Notifications\Concerns;

use App\Services\SettingsService;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Base des courriels du site.
 *
 * Les messages sont volontairement courts, sans jargon et sans information
 * sensible (codex §34). Chacun rappelle le numéro de téléphone, car beaucoup
 * de destinataires préféreront appeler plutôt que répondre par écrit.
 */
trait SimpleMailNotification
{
    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Construit un message avec la signature et le rappel téléphonique.
     *
     * @param  array<int, string>  $lines
     */
    protected function baseMail(string $subject, string $greeting, array $lines): MailMessage
    {
        $settings = app(SettingsService::class);

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting($greeting);

        foreach ($lines as $line) {
            $mail->line($line);
        }

        if ($settings->hasPhone()) {
            $mail->line(__('mail.common.phone_reminder', [
                'phone' => $settings->phoneDisplay(),
            ]));
        }

        return $mail
            ->salutation(__('mail.common.salutation', [
                'name' => $settings->string('adviser_name') ?: $settings->string('site_name'),
            ]));
    }
}
