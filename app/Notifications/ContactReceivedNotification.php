<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ContactRequest;
use App\Notifications\Concerns\SimpleMailNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Accusé de réception d'un message envoyé depuis le site.
 */
class ContactReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable, SimpleMailNotification;

    public function __construct(
        public readonly ContactRequest $contactRequest,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        return $this->baseMail(
            subject: __('mail.contact_received.subject'),
            greeting: __('mail.common.greeting', ['name' => $this->contactRequest->first_name]),
            lines: [
                __('mail.contact_received.intro'),
                __('mail.contact_received.reference', [
                    'reference' => $this->contactRequest->reference,
                ]),
                __('mail.contact_received.delay'),
            ],
        );
    }
}
