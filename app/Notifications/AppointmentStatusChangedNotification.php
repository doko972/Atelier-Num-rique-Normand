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
 * Information de la personne lorsque sa demande change d'état.
 */
class AppointmentStatusChangedNotification extends Notification implements ShouldQueue
{
    use Queueable, SimpleMailNotification;

    public function __construct(
        public readonly Appointment $appointment,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $lines = [
            __('mail.appointment_status.intro', [
                'reference' => $this->appointment->reference,
                'status' => $this->appointment->status->label(),
            ]),
        ];

        if ($this->appointment->scheduled_for !== null) {
            $lines[] = __('mail.appointment_status.scheduled', [
                'date' => $this->appointment->scheduled_for->locale('fr')->isoFormat('dddd D MMMM YYYY [à] HH[ h ]mm'),
            ]);
        }

        if ($this->appointment->location !== null) {
            $lines[] = __('mail.appointment_status.location', [
                'location' => $this->appointment->location->name,
                'address' => $this->appointment->location->fullAddress(),
            ]);
        }

        $lines[] = __('mail.appointment_status.change_request');

        return $this->baseMail(
            subject: __('mail.appointment_status.subject', [
                'reference' => $this->appointment->reference,
            ]),
            greeting: __('mail.common.greeting', ['name' => $this->appointment->first_name]),
            lines: $lines,
        );
    }
}
