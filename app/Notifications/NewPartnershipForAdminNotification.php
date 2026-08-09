<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\PartnershipRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerte interne : une structure souhaite un partenariat.
 */
class NewPartnershipForAdminNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly PartnershipRequest $partnershipRequest,
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
            ->subject(__('mail.admin_new_partnership.subject', [
                'reference' => $this->partnershipRequest->reference,
            ]))
            ->greeting(__('mail.admin_new_appointment.greeting'))
            ->line(__('mail.admin_new_partnership.intro', [
                'organisation' => $this->partnershipRequest->organisation_name,
                'type' => $this->partnershipRequest->organisation_type->label(),
            ]))
            ->action(
                __('mail.admin_new_partnership.action'),
                route('admin.partnership-requests.show', $this->partnershipRequest),
            )
            ->line(__('mail.admin_new_appointment.privacy_note'));
    }
}
