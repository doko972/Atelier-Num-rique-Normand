<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\InterventionActivity;
use App\Models\Intervention;
use App\Support\SapYearSummary;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Suivi de la répartition SAP / hors SAP du chiffre d'affaires.
 */
class SapComplianceService
{
    public function yearSummary(int $year): SapYearSummary
    {
        $current = $this->totalsFor($year);
        $previous = $this->totalsFor($year - 1);

        return new SapYearSummary(
            year: $year,
            sapCents: $current['sap_cents'],
            otherCents: $current['other_cents'],
            sapMinutes: $current['sap_minutes'],
            otherMinutes: $current['other_minutes'],
            previousYearTotalCents: $previous['sap_cents'] + $previous['other_cents'],
        );
    }

    /**
     * Chiffres par trimestre, dans la forme des états d'activité SAP :
     * heures et chiffre d'affaires par activité, nombre de clients
     * particuliers, et le hors SAP à part.
     *
     * @return array<int, array<string, mixed>>
     */
    public function quarterlyBreakdown(int $year): array
    {
        $interventions = Intervention::query()
            ->inYear($year)
            ->get(['performed_on', 'activity', 'client_name', 'duration_minutes', 'amount_cents']);

        $quarters = [];

        foreach (range(1, 4) as $quarter) {
            /** @var Collection<int, Intervention> $rows */
            $rows = $interventions->filter(
                fn (Intervention $intervention): bool => $intervention->performed_on->quarter === $quarter,
            );

            $sap = $rows->filter(fn (Intervention $intervention): bool => $intervention->isSap());
            $other = $rows->reject(fn (Intervention $intervention): bool => $intervention->isSap());

            $groups = [];

            foreach ([InterventionActivity::GROUP_IT, InterventionActivity::GROUP_ADMINISTRATIVE] as $group) {
                $inGroup = $sap->filter(
                    fn (Intervention $intervention): bool => $intervention->activity->sapGroup() === $group,
                );

                $groups[$group] = [
                    'minutes' => (int) $inGroup->sum('duration_minutes'),
                    'cents' => (int) $inGroup->sum('amount_cents'),
                    'clients' => $this->distinctClients($inGroup),
                ];
            }

            $quarters[$quarter] = [
                'groups' => $groups,
                'sap_minutes' => (int) $sap->sum('duration_minutes'),
                'sap_cents' => (int) $sap->sum('amount_cents'),
                'sap_clients' => $this->distinctClients($sap),
                'other_minutes' => (int) $other->sum('duration_minutes'),
                'other_cents' => (int) $other->sum('amount_cents'),
            ];
        }

        return $quarters;
    }

    /**
     * Années pour lesquelles au moins une prestation existe, plus l'année
     * en cours.
     *
     * @return array<int, int>
     */
    public function availableYears(): array
    {
        $first = Intervention::query()->min('performed_on');
        $start = $first === null ? (int) now()->year : (int) substr((string) $first, 0, 4);

        return range((int) now()->year, min($start, (int) now()->year));
    }

    /**
     * @return array{sap_cents: int, other_cents: int, sap_minutes: int, other_minutes: int}
     */
    protected function totalsFor(int $year): array
    {
        $sum = fn (bool $sap, string $column): int => (int) Intervention::query()
            ->inYear($year)
            ->sap($sap)
            ->sum($column);

        return [
            'sap_cents' => $sum(true, 'amount_cents'),
            'other_cents' => $sum(false, 'amount_cents'),
            'sap_minutes' => $sum(true, 'duration_minutes'),
            'other_minutes' => $sum(false, 'duration_minutes'),
        ];
    }

    /**
     * Nombre de clients distincts, à la casse et aux espaces près.
     *
     * @param  Collection<int, Intervention>  $interventions
     */
    protected function distinctClients(Collection $interventions): int
    {
        return $interventions
            ->map(fn (Intervention $intervention): string => Str::of($intervention->client_name)->squish()->lower()->ascii()->toString())
            ->unique()
            ->count();
    }
}
