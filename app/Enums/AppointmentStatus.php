<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Statuts administratifs d'une demande de rendez-vous (codex §11).
 */
enum AppointmentStatus: string
{
    use HasLabel;

    case New = 'new';
    case ToCall = 'to_call';
    case WaitingInformation = 'waiting_information';
    case Proposed = 'proposed';
    case Confirmed = 'confirmed';
    case Done = 'done';
    case Cancelled = 'cancelled';
    case NoFollowUp = 'no_follow_up';
    case Archived = 'archived';

    /**
     * La demande demande-t-elle encore une action du conseiller ?
     */
    public function isOpen(): bool
    {
        return in_array(
            $this,
            [self::New, self::ToCall, self::WaitingInformation, self::Proposed, self::Confirmed],
            strict: true,
        );
    }

    /**
     * La demande est-elle close (comptage de la durée de conservation) ?
     */
    public function isClosed(): bool
    {
        return ! $this->isOpen();
    }

    /**
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::New => [self::ToCall, self::WaitingInformation, self::Proposed, self::Cancelled, self::NoFollowUp],
            self::ToCall => [self::WaitingInformation, self::Proposed, self::Cancelled, self::NoFollowUp],
            self::WaitingInformation => [self::ToCall, self::Proposed, self::Cancelled, self::NoFollowUp],
            self::Proposed => [self::Confirmed, self::ToCall, self::Cancelled, self::NoFollowUp],
            self::Confirmed => [self::Done, self::Cancelled],
            self::Done, self::Cancelled, self::NoFollowUp => [self::Archived],
            self::Archived => [],
        };
    }

    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), strict: true);
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::New => 'danger',
            self::ToCall, self::WaitingInformation => 'warning',
            self::Proposed => 'info',
            self::Confirmed, self::Done => 'success',
            self::Cancelled, self::NoFollowUp => 'neutral',
            self::Archived => 'neutral',
        };
    }
}
