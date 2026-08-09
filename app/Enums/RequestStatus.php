<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Statuts communs aux demandes de contact et de partenariat.
 */
enum RequestStatus: string
{
    use HasLabel;

    case New = 'new';
    case InProgress = 'in_progress';
    case Answered = 'answered';
    case QuoteSent = 'quote_sent';
    case Accepted = 'accepted';
    case Declined = 'declined';
    case Closed = 'closed';
    case Archived = 'archived';

    /**
     * Statuts proposés pour une demande de contact simple.
     *
     * @return array<int, self>
     */
    public static function contactCases(): array
    {
        return [self::New, self::InProgress, self::Answered, self::Closed, self::Archived];
    }

    /**
     * Statuts proposés pour une demande de partenariat professionnel.
     *
     * @return array<int, self>
     */
    public static function partnershipCases(): array
    {
        return [
            self::New,
            self::InProgress,
            self::QuoteSent,
            self::Accepted,
            self::Declined,
            self::Archived,
        ];
    }

    public function isOpen(): bool
    {
        return in_array(
            $this,
            [self::New, self::InProgress, self::QuoteSent],
            strict: true,
        );
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::New => 'danger',
            self::InProgress, self::QuoteSent => 'warning',
            self::Answered, self::Accepted => 'success',
            self::Declined => 'neutral',
            self::Closed, self::Archived => 'neutral',
        };
    }
}
