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
 * Une place s'est libérée : la personne quitte la liste d'attente.
 */
class SeatAvailableNotification extends Notification implements ShouldQueue
{
    use Queueable, SimpleMailNotification;

    public function __construct(
        public readonly WorkshopRegistration $registration,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $workshop = $this->registration->workshop;

        return $this->baseMail(
            subject: __('mail.seat_available.subject', ['title' => $workshop->title]),
            greeting: __('mail.common.greeting', ['name' => $this->registration->first_name]),
            lines: [
                __('mail.seat_available.intro', ['title' => $workshop->title]),
                __('mail.seat_available.when', [
                    'date' => $workshop->startsAt()->locale('fr')->isoFormat('dddd D MMMM YYYY'),
                    'start' => $workshop->startsAt()->format('H\\hi'),
                ]),
                __('mail.seat_available.confirm'),
                __('mail.seat_available.reference', [
                    'reference' => $this->registration->reference,
                ]),
            ],
        );
    }
}
