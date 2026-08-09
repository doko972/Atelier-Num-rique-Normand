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
 * Confirmation d'inscription à un atelier.
 */
class RegistrationConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable, SimpleMailNotification;

    public function __construct(
        public readonly WorkshopRegistration $registration,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $workshop = $this->registration->workshop;

        $lines = [
            __('mail.registration_confirmed.intro', ['title' => $workshop->title]),
            __('mail.registration_confirmed.when', [
                'date' => $workshop->startsAt()->locale('fr')->isoFormat('dddd D MMMM YYYY'),
                'start' => $workshop->startsAt()->format('H\\hi'),
                'end' => $workshop->endsAt()->format('H\\hi'),
            ]),
        ];

        if ($workshop->location !== null) {
            $lines[] = __('mail.registration_confirmed.where', [
                'location' => $workshop->location->name,
                'address' => $workshop->location->fullAddress(),
            ]);
        }

        if ($workshop->own_device_allowed) {
            $lines[] = __('mail.registration_confirmed.bring_device');
        }

        $lines[] = __('mail.registration_confirmed.reference', [
            'reference' => $this->registration->reference,
        ]);
        $lines[] = __('mail.registration_confirmed.cancel_note');

        return $this->baseMail(
            subject: __('mail.registration_confirmed.subject', ['title' => $workshop->title]),
            greeting: __('mail.common.greeting', ['name' => $this->registration->first_name]),
            lines: $lines,
        );
    }
}
