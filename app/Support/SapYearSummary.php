<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Bilan SAP / hors SAP d'une année civile.
 *
 * Deux mesures, parce qu'elles ne disent pas la même chose :
 *
 * - la **part hors SAP de l'année en cours** — la règle des 70 / 30 telle
 *   qu'on la pilote au quotidien ;
 * - le **plafond légal** : le chiffre d'affaires hors SAP de l'année ne doit
 *   pas dépasser 30 % du chiffre d'affaires total de l'année civile
 *   précédente (article L. 7232-1-2 du code du travail, dispense des
 *   micro-entrepreneurs depuis 2025).
 *
 * L'alerte se déclenche dès que l'une des deux approche la limite : mieux
 * vaut un avertissement de trop qu'une perte de la déclaration SAP.
 */
final readonly class SapYearSummary
{
    /** Part maximale du hors SAP, en pourcentage. */
    public const int OTHER_LIMIT_PERCENT = 30;

    /** Seuil d'avertissement, en pourcentage de la limite. */
    public const int WARNING_PERCENT_OF_LIMIT = 80;

    public function __construct(
        public int $year,
        public int $sapCents,
        public int $otherCents,
        public int $sapMinutes,
        public int $otherMinutes,
        public int $previousYearTotalCents,
    ) {}

    public function totalCents(): int
    {
        return $this->sapCents + $this->otherCents;
    }

    /**
     * Part du hors SAP dans le chiffre d'affaires de l'année, en %.
     */
    public function otherShare(): ?float
    {
        if ($this->totalCents() === 0) {
            return null;
        }

        return round($this->otherCents * 100 / $this->totalCents(), 1);
    }

    public function sapShare(): ?float
    {
        $other = $this->otherShare();

        return $other === null ? null : round(100 - $other, 1);
    }

    /**
     * Aucune activité l'année précédente : le plafond légal ne peut pas se
     * calculer. Le cas d'une première année reste à confirmer auprès de la
     * DDETS.
     */
    public function isFirstYear(): bool
    {
        return $this->previousYearTotalCents === 0;
    }

    /**
     * Plafond légal du hors SAP pour l'année, en centimes.
     */
    public function legalCapCents(): ?int
    {
        if ($this->isFirstYear()) {
            return null;
        }

        return intdiv($this->previousYearTotalCents * self::OTHER_LIMIT_PERCENT, 100);
    }

    /**
     * Ce qu'il reste à facturer hors SAP avant d'atteindre le plafond légal.
     * Négatif en cas de dépassement.
     */
    public function legalRemainingCents(): ?int
    {
        $cap = $this->legalCapCents();

        return $cap === null ? null : $cap - $this->otherCents;
    }

    /**
     * État global : `empty`, `ok`, `warning` ou `over`.
     */
    public function status(): string
    {
        if ($this->totalCents() === 0) {
            return 'empty';
        }

        $usage = [
            // Part de l'année rapportée à la limite de 30 %.
            $this->otherShare() * 100 / self::OTHER_LIMIT_PERCENT,
        ];

        $cap = $this->legalCapCents();

        if ($cap !== null) {
            $usage[] = $cap === 0 ? ($this->otherCents > 0 ? INF : 0) : $this->otherCents * 100 / $cap;
        }

        $worst = max($usage);

        return match (true) {
            $worst > 100 => 'over',
            $worst >= self::WARNING_PERCENT_OF_LIMIT => 'warning',
            default => 'ok',
        };
    }

    /**
     * Variante visuelle associée à l'état.
     */
    public function statusVariant(): string
    {
        return match ($this->status()) {
            'over' => 'danger',
            'warning' => 'warning',
            'ok' => 'success',
            default => 'neutral',
        };
    }

    public function needsAttention(): bool
    {
        return in_array($this->status(), ['warning', 'over'], true);
    }
}
