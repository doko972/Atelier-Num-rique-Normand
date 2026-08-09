<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Déconnecte immédiatement un compte désactivé ou supprimé.
 *
 * La désactivation doit prendre effet sans attendre l'expiration de la
 * session : c'est le seul moyen de couper l'accès à un ancien collaborateur.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && ! $user->is_active) {
            Log::channel('auth')->warning('Session interrompue : compte désactivé.', [
                'user_id' => $user->getKey(),
            ]);

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('admin.login')
                ->withErrors(['email' => __('auth.account_disabled')]);
        }

        return $next($request);
    }
}
