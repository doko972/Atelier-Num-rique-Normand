<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\ContactRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerte interne : un message attend une réponse.
 */
class NewContactForAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly ContactRequest $contactRequest,
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
            ->subject(__('mail.admin_new_contact.subject', [
                'reference' => $this->contactRequest->reference,
            ]))
            ->greeting(__('mail.admin_new_appointment.greeting'))
            ->line(__('mail.admin_new_contact.intro', [
                'subject' => $this->contactRequest->subject,
            ]))
            ->action(
                __('mail.admin_new_contact.action'),
                route('admin.contact-requests.show', $this->contactRequest),
            )
            ->line(__('mail.admin_new_appointment.privacy_note'));
    }
}
