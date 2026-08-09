<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Cycle de vie des contenus éditoriaux (services, articles, fiches, pages).
 */
enum ContentStatus: string
{
    use HasLabel;

    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    /**
     * Le contenu est-il visible par le public ?
     */
    public function isPublic(): bool
    {
        return $this === self::Published;
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::Published => 'success',
            self::Archived => 'warning',
        };
    }
}
