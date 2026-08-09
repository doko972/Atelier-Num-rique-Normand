<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Niveau requis pour un atelier ou une fiche pratique.
 *
 * Le vocabulaire évite tout jugement : « Je débute » plutôt que « Débutant ».
 */
enum SkillLevel: string
{
    use HasLabel;

    case Beginner = 'beginner';
    case Intermediate = 'intermediate';
    case Advanced = 'advanced';
    case Everyone = 'everyone';

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Beginner, self::Everyone => 'success',
            self::Intermediate => 'info',
            self::Advanced => 'warning',
        };
    }
}
