<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Appointment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerte interne : une nouvelle demande attend une réponse.
 *
 * Le courriel ne contient ni le besoin exprimé ni les coordonnées : ces
 * informations restent dans le back-office, derrière l'authentification.
 */
class NewAppointmentForAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Appointment $appointment,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('mail.admin_new_appointment.subject', [
                'reference' => $this->appointment->reference,
            ]))
            ->greeting(__('mail.admin_new_appointment.greeting'))
            ->line(__('mail.admin_new_appointment.intro', [
                'type' => $this->appointment->type->label(),
                'municipality' => $this->appointment->municipality?->name
                    ?? $this->appointment->municipality_name
                    ?? __('mail.common.not_specified'),
            ]))
            ->action(
                __('mail.admin_new_appointment.action'),
                route('admin.appointments.show', $this->appointment),
            )
            ->line(__('mail.admin_new_appointment.privacy_note'));
    }
}
