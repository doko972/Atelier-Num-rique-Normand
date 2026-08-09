<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\PartnershipRequest;
use App\Notifications\Concerns\SimpleMailNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Accusé de réception d'une demande de partenariat.
 */
class PartnershipReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable, SimpleMailNotification;

    public function __construct(
        public readonly PartnershipRequest $partnershipRequest,
    ) {}

    public function toMail(object $notifiable): MailMessage
    {
        $lines = [
            __('mail.partnership_received.intro', [
                'organisation' => $this->partnershipRequest->organisation_name,
            ]),
            __('mail.partnership_received.reference', [
                'reference' => $this->partnershipRequest->reference,
            ]),
        ];

        if ($this->partnershipRequest->quote_requested) {
            $lines[] = __('mail.partnership_received.quote');
        }

        $lines[] = __('mail.partnership_received.delay');

        return $this->baseMail(
            subject: __('mail.partnership_received.subject'),
            greeting: __('mail.common.greeting', [
                'name' => $this->partnershipRequest->contact_name,
            ]),
            lines: $lines,
        );
    }
}
