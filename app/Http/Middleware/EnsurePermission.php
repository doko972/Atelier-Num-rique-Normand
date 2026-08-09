<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contrôle d'une permission avant l'accès à une section du back-office.
 *
 * Ce filtre protège la navigation ; les Policies restent la référence pour
 * chaque action individuelle.
 */
class EnsurePermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        abort_unless(
            $request->user()?->hasPermission($permission) ?? false,
            403,
            __('admin.errors.missing_permission'),
        );

        return $next($request);
    }
}
