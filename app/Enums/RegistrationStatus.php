<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Statuts d'une inscription à un atelier.
 */
enum RegistrationStatus: string
{
    use HasLabel;

    case Pending = 'pending';
    case Confirmed = 'confirmed';
    case WaitingList = 'waiting_list';
    case Cancelled = 'cancelled';
    case Attended = 'attended';
    case Absent = 'absent';

    /**
     * L'inscription occupe-t-elle une place dans l'atelier ?
     */
    public function occupiesSeat(): bool
    {
        return in_array(
            $this,
            [self::Pending, self::Confirmed, self::Attended],
            strict: true,
        );
    }

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::Pending => [self::Confirmed, self::WaitingList, self::Cancelled],
            self::Confirmed => [self::Cancelled, self::Attended, self::Absent],
            self::WaitingList => [self::Confirmed, self::Cancelled],
            self::Cancelled => [self::WaitingList],
            self::Attended, self::Absent => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), strict: true);
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Confirmed, self::Attended => 'success',
            self::WaitingList => 'info',
            self::Cancelled, self::Absent => 'danger',
        };
    }
}
