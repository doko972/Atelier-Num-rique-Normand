<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ConsentPurpose;
use App\Enums\RegistrationStatus;
use App\Enums\WorkshopStatus;
use App\Models\User;
use App\Models\Workshop;
use App\Models\WorkshopRegistration;
use App\Notifications\RegistrationConfirmedNotification;
use App\Notifications\SeatAvailableNotification;
use App\Notifications\WaitingListNotification;
use App\Support\Privacy;
use App\Support\RegistrationOutcome;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Inscriptions aux ateliers : places, liste d'attente, annulations.
 *
 * Toutes les écritures qui touchent au nombre de places passent par une
 * transaction avec verrouillage de l'atelier : deux personnes qui valident le
 * formulaire au même instant ne peuvent pas prendre la même dernière place.
 */
class WorkshopRegistrationService
{
    public function __construct(
        protected ConsentService $consents,
    ) {}

    /**
     * Inscrit une personne, ou la place en liste d'attente si l'atelier est
     * complet.
     *
     * @param  array<string, mixed>  $data
     */
    public function register(Workshop $workshop, array $data, ?User $registrar = null): RegistrationOutcome
    {
        $registration = DB::transaction(function () use ($workshop, $data, $registrar): WorkshopRegistration {
            // Verrouille la ligne de l'atelier le temps de compter les places.
            $locked = Workshop::query()->lockForUpdate()->findOrFail($workshop->getKey());

            $seatAvailable = $locked->remainingSeats() > 0;

            $status = $seatAvailable
                ? RegistrationStatus::Pending
                : RegistrationStatus::WaitingList;

            $registration = WorkshopRegistration::create([
                ...$data,
                'workshop_id' => $locked->getKey(),
                'status' => $status,
                'waiting_position' => $seatAvailable ? null : $this->nextWaitingPosition($locked),
                'registered_by_phone' => $registrar !== null,
                'registered_by' => $registrar?->getKey(),
                'consent_given' => true,
                'consent_given_at' => now(),
            ]);

            $registration->forceFill([
                'ip_hash' => Privacy::hashIp(request()->ip()),
            ])->save();

            $this->consents->record($registration, ConsentPurpose::WorkshopRegistration);

            if ($registration->voice_message_allowed) {
                $this->consents->record($registration, ConsentPurpose::VoiceMessage);
            }

            $this->refreshWorkshopStatus($locked);

            return $registration;
        });

        Log::channel('ateliers')->info('Nouvelle inscription.', [
            'reference' => $registration->reference,
            'workshop' => $workshop->slug,
            'status' => $registration->status->value,
        ]);

        $this->notifyRegistration($registration);

        return new RegistrationOutcome(
            registration: $registration,
            onWaitingList: $registration->status === RegistrationStatus::WaitingList,
        );
    }

    /**
     * Change le statut d'une inscription et réévalue la liste d'attente.
     *
     * @throws \DomainException si la transition n'est pas autorisée
     */
    public function changeStatus(
        WorkshopRegistration $registration,
        RegistrationStatus $status,
    ): WorkshopRegistration {
        if ($registration->status === $status) {
            return $registration;
        }

        if (! $registration->status->canTransitionTo($status)) {
            throw new \DomainException(__('admin.registrations.invalid_transition', [
                'from' => $registration->status->label(),
                'to' => $status->label(),
            ]));
        }

        DB::transaction(function () use ($registration, $status): void {
            $registration->status = $status;

            if ($status === RegistrationStatus::Cancelled) {
                $registration->cancelled_at = now();
                $registration->waiting_position = null;
            }

            if ($status !== RegistrationStatus::WaitingList) {
                $registration->waiting_position = null;
            }

            $registration->save();
        });

        // Une place libérée profite immédiatement à la liste d'attente.
        if (! $status->occupiesSeat()) {
            $this->promoteFromWaitingList($registration->workshop);
        }

        $this->refreshWorkshopStatus($registration->workshop->refresh());

        return $registration;
    }

    /**
     * Fait monter la première personne en attente dès qu'une place se libère.
     */
    public function promoteFromWaitingList(Workshop $workshop): ?WorkshopRegistration
    {
        $promoted = DB::transaction(function () use ($workshop): ?WorkshopRegistration {
            $locked = Workshop::query()->lockForUpdate()->findOrFail($workshop->getKey());

            if ($locked->remainingSeats() < 1) {
                return null;
            }

            $next = $locked->waitingList()->first();

            if ($next === null) {
                return null;
            }

            $next->status = RegistrationStatus::Pending;
            $next->waiting_position = null;
            $next->save();

            $this->reorderWaitingList($locked);

            return $next;
        });

        if ($promoted === null) {
            return null;
        }

        Log::channel('ateliers')->info('Place attribuée depuis la liste d’attente.', [
            'reference' => $promoted->reference,
        ]);

        if ($promoted->canReceiveEmail()) {
            Notification::route('mail', $promoted->email)
                ->notify(new SeatAvailableNotification($promoted));
        }

        return $promoted;
    }

    /**
     * Aligne le statut de l'atelier sur le remplissage réel.
     *
     * Un atelier « publié » qui n'a plus de place passe en « complet », et
     * inversement : l'agenda public reste ainsi toujours exact.
     */
    public function refreshWorkshopStatus(Workshop $workshop): void
    {
        if (! $workshop->status->acceptsRegistrations()) {
            return;
        }

        $target = $workshop->isFull() ? WorkshopStatus::Full : WorkshopStatus::Published;

        if ($workshop->status !== $target) {
            $workshop->status = $target;
            $workshop->save();
        }
    }

    protected function nextWaitingPosition(Workshop $workshop): int
    {
        return (int) $workshop->registrations()
            ->where('status', RegistrationStatus::WaitingList)
            ->max('waiting_position') + 1;
    }

    /**
     * Renumérote la liste d'attente sans laisser de trou.
     */
    protected function reorderWaitingList(Workshop $workshop): void
    {
        $workshop->waitingList()
            ->get()
            ->each(function (WorkshopRegistration $registration, int $index): void {
                $registration->waiting_position = $index + 1;
                $registration->save();
            });
    }

    protected function notifyRegistration(WorkshopRegistration $registration): void
    {
        if (! $registration->canReceiveEmail()) {
            return;
        }

        $notification = $registration->status === RegistrationStatus::WaitingList
            ? new WaitingListNotification($registration)
            : new RegistrationConfirmedNotification($registration);

        Notification::route('mail', $registration->email)->notify($notification);
    }
}
