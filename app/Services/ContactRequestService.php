<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ConsentPurpose;
use App\Enums\RequestStatus;
use App\Models\ContactRequest;
use App\Models\PartnershipRequest;
use App\Notifications\ContactReceivedNotification;
use App\Notifications\NewContactForAdminNotification;
use App\Notifications\NewPartnershipForAdminNotification;
use App\Notifications\PartnershipReceivedNotification;
use App\Support\Notifier;
use App\Support\Privacy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Traitement des messages reçus et des demandes de partenariat.
 */
class ContactRequestService
{
    public function __construct(
        protected ConsentService $consents,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createContact(array $data): ContactRequest
    {
        $contact = DB::transaction(function () use ($data): ContactRequest {
            $contact = ContactRequest::create([
                ...$data,
                'status' => RequestStatus::New,
                'consent_given' => true,
                'consent_given_at' => now(),
            ]);

            $contact->forceFill(['ip_hash' => Privacy::hashIp(request()->ip())])->save();

            $this->consents->record($contact, ConsentPurpose::ContactRequest);

            if ($contact->voice_message_allowed) {
                $this->consents->record($contact, ConsentPurpose::VoiceMessage);
            }

            return $contact;
        });

        Log::channel('admin')->info('Nouveau message reçu.', ['reference' => $contact->reference]);

        $context = ['reference' => $contact->reference];

        if ($contact->canReceiveEmail()) {
            Notifier::mail($contact->email, new ContactReceivedNotification($contact), $context);
        }

        $this->notifyAdmin(new NewContactForAdminNotification($contact), $context);

        return $contact;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPartnership(array $data): PartnershipRequest
    {
        $partnership = DB::transaction(function () use ($data): PartnershipRequest {
            $partnership = PartnershipRequest::create([
                ...$data,
                'status' => RequestStatus::New,
                'consent_given' => true,
                'consent_given_at' => now(),
            ]);

            $partnership->forceFill(['ip_hash' => Privacy::hashIp(request()->ip())])->save();

            $this->consents->record($partnership, ConsentPurpose::PartnershipRequest);

            return $partnership;
        });

        Log::channel('admin')->info('Nouvelle demande de partenariat.', [
            'reference' => $partnership->reference,
            'organisation' => $partnership->organisation_name,
        ]);

        $context = ['reference' => $partnership->reference];

        if ($partnership->canReceiveEmail()) {
            Notifier::mail($partnership->email, new PartnershipReceivedNotification($partnership), $context);
        }

        $this->notifyAdmin(new NewPartnershipForAdminNotification($partnership), $context);

        return $partnership;
    }

    /**
     * Marque une demande comme traitée.
     */
    public function markAnswered(ContactRequest|PartnershipRequest $request): void
    {
        $request->status = RequestStatus::Answered;
        $request->answered_at = now();
        $request->save();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    protected function notifyAdmin(mixed $notification, array $context = []): void
    {
        Notifier::mail(config('site.contact.admin_email'), $notification, $context);
    }
}
