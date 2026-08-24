<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Envoi des notifications par courriel, à l'épreuve d'une panne de messagerie.
 *
 * Sur un hébergement mutualisé, les notifications partent de façon synchrone :
 * il n'y a aucun exécutant de file d'attente. Une panne du serveur SMTP
 * remonterait donc jusqu'à la page du visiteur, sous forme d'erreur — alors
 * que sa demande vient d'être enregistrée et porte déjà une référence.
 *
 * Il resoumettrait, croyant avoir échoué, et créerait un doublon.
 *
 * L'échec est donc journalisé et non propagé : la demande est en base, elle
 * sera traitée depuis le back-office même si l'alerte n'est pas partie. Le
 * journal `admin` conserve de quoi diagnostiquer.
 */
class Notifier
{
    /**
     * Notifie une adresse, sans jamais faire échouer l'appelant.
     *
     * @param  array<string, mixed>  $context  Repères ajoutés au journal
     * @return bool Vrai si l'envoi a abouti.
     */
    public static function mail(?string $recipient, mixed $notification, array $context = []): bool
    {
        if (blank($recipient)) {
            return false;
        }

        try {
            Notification::route('mail', $recipient)->notify($notification);

            return true;
        } catch (Throwable $exception) {
            Log::channel('admin')->error('Envoi de notification impossible.', [
                ...$context,
                'notification' => $notification::class,
                // Jamais l'adresse en clair : le journal n'a pas à conserver
                // de donnée personnelle au-delà de ce qui sert au diagnostic.
                'recipient' => Privacy::maskEmail($recipient),
                'error' => $exception->getMessage(),
            ]);

            return false;
        }
    }
}
