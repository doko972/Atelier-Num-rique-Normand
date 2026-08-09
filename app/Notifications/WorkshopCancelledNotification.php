<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\WorkshopRegistration;
use App\Notifications\Concerns\SimpleMailNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Annulation d'un atelier.
 *
 * Le message explique la raison lorsqu'elle a été saisie : une annulation sans
 * explication est très mal vécue par un public déjà peu confiant.
 */
class WorkshopCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable, SimpleMailNotification;

    public function __construct(
        public readonly WorkshopRegistration $registration,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $workshop = $this->registration->workshop;

        $lines = [
            __('mail.workshop_cancelled.intro', [
                'title' => $workshop->title,
                'date' => $workshop->startsAt()->locale('fr')->isoFormat('dddd D MMMM'),
            ]),
        ];

        if (filled($workshop->cancellation_reason)) {
            $lines[] = __('mail.workshop_cancelled.reason', [
                'reason' => $workshop->cancellation_reason,
            ]);
        }

        $lines[] = __('mail.workshop_cancelled.apology');
        $lines[] = __('mail.workshop_cancelled.next_step');

        return $this->baseMail(
            subject: __('mail.workshop_cancelled.subject', ['title' => $workshop->title]),
            greeting: __('mail.common.greeting', ['name' => $this->registration->first_name]),
            lines: $lines,
        );
    }
}
