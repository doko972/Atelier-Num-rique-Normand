<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    // Permet d'appeler $this->authorize() depuis les contrôleurs, ce dont
    // dépend chaque action du back-office.
    use AuthorizesRequests;
}
