<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\ConsentPurpose;
use App\Models\Appointment;
use App\Models\AppointmentNote;
use App\Models\User;
use App\Notifications\AppointmentReceivedNotification;
use App\Notifications\AppointmentStatusChangedNotification;
use App\Notifications\NewAppointmentForAdminNotification;
use App\Support\Privacy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * Logique métier des demandes de rendez-vous.
 */
class AppointmentService
{
    public function __construct(
        protected ConsentService $consents,
    ) {}

    /**
     * Enregistre une demande envoyée depuis le site public.
     *
     * @param  array<string, mixed>  $data
     */
    public function createFromPublicForm(array $data): Appointment
    {
        $appointment = DB::transaction(function () use ($data): Appointment {
            $appointment = Appointment::create([
                ...$data,
                'status' => AppointmentStatus::New,
                'consent_given' => true,
                'consent_given_at' => now(),
                'source' => 'website',
            ]);

            $appointment->forceFill([
                'ip_hash' => Privacy::hashIp(request()->ip()),
            ])->save();

            $this->consents->record($appointment, ConsentPurpose::AppointmentRequest);

            if ($appointment->voice_message_allowed) {
                $this->consents->record($appointment, ConsentPurpose::VoiceMessage);
            }

            return $appointment;
        });

        Log::channel('rdv')->info('Nouvelle demande de rendez-vous.', [
            'reference' => $appointment->reference,
            'type' => $appointment->type->value,
        ]);

        $this->notifyCreation($appointment);

        return $appointment;
    }

    /**
     * Change le statut d'une demande en respectant les transitions permises.
     *
     * @throws \DomainException si la transition n'est pas autorisée
     */
    public function changeStatus(
        Appointment $appointment,
        AppointmentStatus $status,
        ?User $author = null,
        bool $notifyRequester = false,
    ): Appointment {
        if ($appointment->status === $status) {
            return $appointment;
        }

        if (! $appointment->status->canTransitionTo($status)) {
            throw new \DomainException(__('admin.appointments.invalid_transition', [
                'from' => $appointment->status->label(),
                'to' => $status->label(),
            ]));
        }

        $previous = $appointment->status;

        DB::transaction(function () use ($appointment, $status, $previous, $author): void {
            $appointment->status = $status;

            if ($status->isClosed() && $appointment->closed_at === null) {
                $appointment->closed_at = now();
            }

            $appointment->save();

            AppointmentNote::create([
                'appointment_id' => $appointment->getKey(),
                'user_id' => $author?->getKey(),
                'is_system' => true,
                'body' => __('admin.appointments.status_change_note', [
                    'from' => $previous->label(),
                    'to' => $status->label(),
                ]),
            ]);
        });

        Log::channel('rdv')->info('Statut de demande modifié.', [
            'reference' => $appointment->reference,
            'from' => $previous->value,
            'to' => $status->value,
        ]);

        if ($notifyRequester && $appointment->canReceiveEmail()) {
            Notification::route('mail', $appointment->email)
                ->notify(new AppointmentStatusChangedNotification($appointment));
        }

        return $appointment->refresh();
    }

    /**
     * Ajoute une note interne.
     */
    public function addNote(Appointment $appointment, string $body, User $author): AppointmentNote
    {
        return AppointmentNote::create([
            'appointment_id' => $appointment->getKey(),
            'user_id' => $author->getKey(),
            'body' => $body,
            'is_system' => false,
        ]);
    }

    /**
     * Affecte la demande à un conseiller.
     */
    public function assign(Appointment $appointment, ?User $assignee, User $author): Appointment
    {
        $appointment->assigned_to = $assignee?->getKey();
        $appointment->save();

        AppointmentNote::create([
            'appointment_id' => $appointment->getKey(),
            'user_id' => $author->getKey(),
            'is_system' => true,
            'body' => $assignee === null
                ? __('admin.appointments.unassigned_note')
                : __('admin.appointments.assigned_note', ['name' => $assignee->name]),
        ]);

        return $appointment;
    }

    /**
     * Envoie l'accusé de réception et prévient l'administration.
     */
    protected function notifyCreation(Appointment $appointment): void
    {
        if ($appointment->canReceiveEmail()) {
            Notification::route('mail', $appointment->email)
                ->notify(new AppointmentReceivedNotification($appointment));
        }

        $adminEmail = config('site.contact.admin_email');

        if (filled($adminEmail)) {
            Notification::route('mail', $adminEmail)
                ->notify(new NewAppointmentForAdminNotification($appointment));
        }
    }
}
