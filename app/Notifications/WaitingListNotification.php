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
 * L'atelier est complet : la personne est placée en liste d'attente.
 */
class WaitingListNotification extends Notification implements ShouldQueue
{
    use Queueable, SimpleMailNotification;

    public function __construct(
        public readonly WorkshopRegistration $registration,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return $this->baseMail(
            subject: __('mail.waiting_list.subject', [
                'title' => $this->registration->workshop->title,
            ]),
            greeting: __('mail.common.greeting', ['name' => $this->registration->first_name]),
            lines: [
                __('mail.waiting_list.intro', [
                    'title' => $this->registration->workshop->title,
                ]),
                __('mail.waiting_list.position', [
                    'position' => $this->registration->waiting_position,
                ]),
                __('mail.waiting_list.next_step'),
                __('mail.waiting_list.reference', [
                    'reference' => $this->registration->reference,
                ]),
            ],
        );
    }
}
