<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Enums\RegistrationStatus;
use App\Enums\RequestStatus;
use App\Enums\WorkshopStatus;
use App\Models\Appointment;
use App\Models\ContactRequest;
use App\Models\PartnershipRequest;
use App\Models\Workshop;
use App\Models\WorkshopRegistration;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Indicateurs du tableau de bord et bilans d'activité (codex §36).
 *
 * Toutes les valeurs sont agrégées : aucun indicateur ne permet d'isoler une
 * personne.
 */
class StatisticsService
{
    /**
     * Compteurs affichés en haut du tableau de bord.
     *
     * @return array<string, int>
     */
    public function dashboardCounters(): array
    {
        return [
            'new_appointments' => Appointment::query()
                ->where('status', AppointmentStatus::New)
                ->count(),
            'appointments_to_call' => Appointment::query()
                ->where('status', AppointmentStatus::ToCall)
                ->count(),
            'appointments_to_confirm' => Appointment::query()
                ->where('status', AppointmentStatus::Proposed)
                ->count(),
            'overdue_callbacks' => Appointment::query()
                ->open()
                ->whereDate('callback_on', '<', CarbonImmutable::today())
                ->count(),
            'upcoming_workshops' => Workshop::query()
                ->whereIn('status', [WorkshopStatus::Published, WorkshopStatus::Full])
                ->whereDate('date', '>=', CarbonImmutable::today())
                ->count(),
            'recent_registrations' => WorkshopRegistration::query()
                ->where('created_at', '>=', CarbonImmutable::now()->subDays(7))
                ->count(),
            'waiting_list' => WorkshopRegistration::query()
                ->where('status', RegistrationStatus::WaitingList)
                ->count(),
            'unread_messages' => ContactRequest::query()
                ->where('status', RequestStatus::New)
                ->count(),
            'partnership_requests' => PartnershipRequest::query()
                ->open()
                ->count(),
        ];
    }

    /**
     * Places restantes sur les prochains ateliers.
     *
     * @return Collection<int, Workshop>
     */
    public function upcomingWorkshopsWithSeats(int $limit = 5): Collection
    {
        return Workshop::query()
            ->withCount([
                'registrations as active_registrations_count' => fn ($query) => $query->occupyingSeat(),
            ])
            ->whereIn('status', [WorkshopStatus::Published, WorkshopStatus::Full])
            ->upcoming()
            ->limit($limit)
            ->get();
    }

    /**
     * Nombre de demandes par mois sur les douze derniers mois.
     *
     * @return array<string, int>
     */
    public function appointmentsPerMonth(int $months = 12): array
    {
        $since = CarbonImmutable::now()->startOfMonth()->subMonths($months - 1);

        $rows = Appointment::query()
            ->where('created_at', '>=', $since)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as period, COUNT(*) as total")
            ->groupBy('period')
            ->pluck('total', 'period');

        $series = [];

        for ($index = 0; $index < $months; $index++) {
            $month = $since->addMonths($index);
            $key = $month->format('Y-m');
            $series[$key] = (int) ($rows[$key] ?? 0);
        }

        return $series;
    }

    /**
     * Taux de remplissage moyen des ateliers terminés.
     */
    public function averageFillRate(): float
    {
        $workshops = Workshop::query()
            ->withCount([
                'registrations as active_registrations_count' => fn ($query) => $query->occupyingSeat(),
            ])
            ->whereIn('status', [WorkshopStatus::Finished, WorkshopStatus::Archived])
            ->where('capacity', '>', 0)
            ->get();

        if ($workshops->isEmpty()) {
            return 0.0;
        }

        $rate = $workshops->avg(
            fn (Workshop $workshop): float => min(1.0, $workshop->occupiedSeats() / $workshop->capacity),
        );

        return round((float) $rate * 100, 1);
    }

    /**
     * Communes les plus représentées parmi les demandes.
     *
     * @return Collection<int, object{name: string, total: int}>
     */
    public function topMunicipalities(int $limit = 8): Collection
    {
        return Appointment::query()
            ->join('municipalities', 'municipalities.id', '=', 'appointments.municipality_id')
            ->select('municipalities.name', DB::raw('COUNT(*) as total'))
            ->groupBy('municipalities.name')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();
    }

    /**
     * Répartition des demandes par type d'accompagnement.
     *
     * @return array<string, int>
     */
    public function appointmentsByType(): array
    {
        return Appointment::query()
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type')
            ->mapWithKeys(fn (int $total, string $type): array => [
                AppointmentType::from($type)->label() => $total,
            ])
            ->all();
    }

    /**
     * Taux d'annulation des inscriptions, en pourcentage.
     */
    public function cancellationRate(): float
    {
        $total = WorkshopRegistration::query()->count();

        if ($total === 0) {
            return 0.0;
        }

        $cancelled = WorkshopRegistration::query()
            ->whereIn('status', [RegistrationStatus::Cancelled, RegistrationStatus::Absent])
            ->count();

        return round($cancelled / $total * 100, 1);
    }
}
