<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabel;

/**
 * Appareil concerné par une demande ou apporté à un atelier.
 */
enum DeviceType: string
{
    use HasLabel;

    case Computer = 'computer';
    case Laptop = 'laptop';
    case Smartphone = 'smartphone';
    case Tablet = 'tablet';
    case Printer = 'printer';
    case None = 'none';
    case Other = 'other';
}
