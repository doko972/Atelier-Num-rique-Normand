<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Tranche d'âge, toujours facultative.
 *
 * Elle n'est collectée que pour établir des bilans agrégés à destination des
 * partenaires financeurs, jamais pour individualiser un suivi.
 */
enum AgeRange: string
{
    use HasLabel;

    case Under60 = 'under_60';
    case From60To69 = '60_69';
    case From70To79 = '70_79';
    case Over80 = 'over_80';
    case Undisclosed = 'undisclosed';
}
