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
 * Rappel envoyé quelques jours avant l'atelier.
 */
class WorkshopReminderNotification extends Notification implements ShouldQueue
{
    use Queueable, SimpleMailNotification;

    public function __construct(
        public readonly WorkshopRegistration $registration,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $workshop = $this->registration->workshop;

        $lines = [
            __('mail.workshop_reminder.intro', [
                'title' => $workshop->title,
                'date' => $workshop->startsAt()->locale('fr')->isoFormat('dddd D MMMM'),
                'start' => $workshop->startsAt()->format('H\\hi'),
            ]),
        ];

        if ($workshop->location !== null) {
            $lines[] = __('mail.workshop_reminder.where', [
                'location' => $workshop->location->name,
                'address' => $workshop->location->fullAddress(),
            ]);
        }

        $lines[] = $workshop->equipment_provided
            ? __('mail.workshop_reminder.equipment_provided')
            : __('mail.workshop_reminder.bring_equipment');

        $lines[] = __('mail.workshop_reminder.cancel_note');

        return $this->baseMail(
            subject: __('mail.workshop_reminder.subject', ['title' => $workshop->title]),
            greeting: __('mail.common.greeting', ['name' => $this->registration->first_name]),
            lines: $lines,
        );
    }
}
