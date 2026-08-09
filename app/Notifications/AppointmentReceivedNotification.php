<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Appointment;
use App\Notifications\Concerns\SimpleMailNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Accusé de réception envoyé à la personne qui demande un rendez-vous.
 */
class AppointmentReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable, SimpleMailNotification;

    public function __construct(
        public readonly Appointment $appointment,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return $this->baseMail(
            subject: __('mail.appointment_received.subject'),
            greeting: __('mail.common.greeting', ['name' => $this->appointment->first_name]),
            lines: [
                __('mail.appointment_received.intro'),
                __('mail.appointment_received.reference', [
                    'reference' => $this->appointment->reference,
                ]),
                __('mail.appointment_received.next_step'),
                __('mail.common.no_password_reminder'),
            ],
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return ['reference' => $this->appointment->reference];
    }
}
