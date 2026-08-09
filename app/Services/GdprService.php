<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Appointment;
use App\Models\ContactRequest;
use App\Models\DataDeletionRequest;
use App\Models\PartnershipRequest;
use App\Models\WorkshopRegistration;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Exercice des droits RGPD (codex §27).
 *
 * L'effacement se traduit par une anonymisation : les bilans agrégés attendus
 * par les communes financeuses restent exacts, mais plus aucune donnée ne
 * permet d'identifier la personne.
 */
class GdprService
{
    /**
     * Recherche toutes les traces liées à un numéro de téléphone ou à une
     * adresse électronique.
     *
     * @return array{
     *     appointments: Collection<int, Appointment>,
     *     registrations: Collection<int, WorkshopRegistration>,
     *     contacts: Collection<int, ContactRequest>,
     *     partnerships: Collection<int, PartnershipRequest>,
     * }
     */
    public function findRecords(?string $email, ?string $phone): array
    {
        $normalisedPhone = $this->normalisePhone($phone);

        return [
            'appointments' => $this->matching(Appointment::query(), $email, $normalisedPhone)->get(),
            'registrations' => $this->matching(WorkshopRegistration::query(), $email, $normalisedPhone)->get(),
            'contacts' => $this->matching(ContactRequest::query(), $email, $normalisedPhone)->get(),
            'partnerships' => PartnershipRequest::query()
                ->notAnonymised()
                ->when($email, fn ($query) => $query->orWhere('email', $email))
                ->when($normalisedPhone, fn ($query) => $query->orWhere('phone', 'like', "%{$normalisedPhone}%"))
                ->get(),
        ];
    }

    /**
     * Exporte, au format lisible, toutes les données détenues sur une personne.
     *
     * @return array<string, mixed>
     */
    public function buildExport(?string $email, ?string $phone): array
    {
        $records = $this->findRecords($email, $phone);

        return [
            'genere_le' => now()->toIso8601String(),
            'criteres' => array_filter([
                'adresse_electronique' => $email,
                'telephone' => $phone,
            ]),
            'demandes_de_rendez_vous' => $records['appointments']
                ->map(fn (Appointment $appointment): array => [
                    'reference' => $appointment->reference,
                    'recue_le' => $appointment->created_at?->toDateString(),
                    'type' => $appointment->type->label(),
                    'statut' => $appointment->status->label(),
                    'commune' => $appointment->municipality?->name ?? $appointment->municipality_name,
                    'besoin_exprime' => $appointment->need_description,
                    'disponibilites' => $appointment->availability,
                    'notes_internes' => $appointment->notes
                        ->map(fn ($note): array => [
                            'date' => $note->created_at?->toDateString(),
                            'contenu' => $note->body,
                        ])->all(),
                ])->all(),
            'inscriptions_aux_ateliers' => $records['registrations']
                ->map(fn (WorkshopRegistration $registration): array => [
                    'reference' => $registration->reference,
                    'atelier' => $registration->workshop?->title,
                    'date_atelier' => $registration->workshop?->date?->toDateString(),
                    'statut' => $registration->status->label(),
                    'besoin_particulier' => $registration->special_needs,
                ])->all(),
            'messages_envoyes' => $records['contacts']
                ->map(fn (ContactRequest $contact): array => [
                    'reference' => $contact->reference,
                    'envoye_le' => $contact->created_at?->toDateString(),
                    'sujet' => $contact->subject,
                    'message' => $contact->message,
                    'statut' => $contact->status->label(),
                ])->all(),
            'demandes_de_partenariat' => $records['partnerships']
                ->map(fn (PartnershipRequest $partnership): array => [
                    'reference' => $partnership->reference,
                    'structure' => $partnership->organisation_name,
                    'envoyee_le' => $partnership->created_at?->toDateString(),
                    'statut' => $partnership->status->label(),
                ])->all(),
        ];
    }

    /**
     * Anonymise les enregistrements correspondant au périmètre demandé.
     *
     * @return int nombre d'enregistrements traités
     */
    public function anonymise(DataDeletionRequest $request): int
    {
        $records = $this->findRecords($request->requester_email, $request->requester_phone);

        $scopes = match ($request->scope) {
            DataDeletionRequest::SCOPE_APPOINTMENTS => ['appointments'],
            DataDeletionRequest::SCOPE_REGISTRATIONS => ['registrations'],
            DataDeletionRequest::SCOPE_CONTACTS => ['contacts', 'partnerships'],
            default => ['appointments', 'registrations', 'contacts', 'partnerships'],
        };

        $count = DB::transaction(function () use ($records, $scopes): int {
            $count = 0;

            foreach ($scopes as $scope) {
                foreach ($records[$scope] as $record) {
                    if ($record->anonymise()) {
                        $count++;
                    }
                }
            }

            return $count;
        });

        Log::channel('rgpd')->notice('Demande d’effacement exécutée.', [
            'reference' => $request->reference,
            'scope' => $request->scope,
            'records' => $count,
        ]);

        return $count;
    }

    /**
     * Purge planifiée : anonymise ce qui dépasse la durée de conservation.
     *
     * @return array<string, int>
     */
    public function purgeExpiredRecords(): array
    {
        $retention = config('site.retention');
        $summary = [];

        $summary['appointments'] = $this->purge(
            Appointment::query()
                ->notAnonymised()
                ->whereNotNull('closed_at')
                ->where('closed_at', '<', now()->subDays($retention['appointments'])),
        );

        $summary['registrations'] = $this->purge(
            WorkshopRegistration::query()
                ->notAnonymised()
                ->whereHas(
                    'workshop',
                    fn ($query) => $query->whereDate('date', '<', now()->subDays($retention['registrations'])),
                ),
        );

        $summary['contacts'] = $this->purge(
            ContactRequest::query()
                ->notAnonymised()
                ->where('created_at', '<', now()->subDays($retention['contacts'])),
        );

        $summary['partnerships'] = $this->purge(
            PartnershipRequest::query()
                ->notAnonymised()
                ->where('created_at', '<', now()->subDays($retention['partnerships'])),
        );

        Log::channel('rgpd')->info('Purge automatique effectuée.', $summary);

        return $summary;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<*>  $query
     */
    protected function purge(Builder $query): int
    {
        $count = 0;

        $query->chunkById(200, function (Collection $records) use (&$count): void {
            foreach ($records as $record) {
                if ($record->anonymise()) {
                    $count++;
                }
            }
        });

        return $count;
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<*>  $query
     * @return \Illuminate\Database\Eloquent\Builder<*>
     */
    protected function matching(
        Builder $query,
        ?string $email,
        ?string $phone,
    ): Builder {
        return $query
            ->notAnonymised()
            ->where(function ($query) use ($email, $phone): void {
                // Sans critère, aucune correspondance : on ne renvoie jamais
                // la totalité de la base par accident.
                $query->whereRaw('1 = 0');

                if (filled($email)) {
                    $query->orWhere('email', $email);
                }

                if (filled($phone)) {
                    $query->orWhere('phone', 'like', "%{$phone}%");
                }
            });
    }

    /**
     * Retire les séparateurs pour comparer des numéros saisis différemment.
     */
    protected function normalisePhone(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        return preg_replace('/\D+/', '', $phone) ?: null;
    }
}
