<?php

declare(strict_types=1);

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Illuminate\Validation\ValidationException;

/**
 * Mot de passe oublié et réinitialisation.
 */
class PasswordResetController extends Controller
{
    public function requestForm(): View
    {
        return view('auth.forgot-password');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email', 'max:180'],
        ], attributes: ['email' => __('auth.fields.email')]);

        $status = Password::sendResetLink($request->only('email'));

        Log::channel('auth')->info('Demande de réinitialisation de mot de passe.', [
            'email' => $request->string('email')->value(),
            'status' => $status,
        ]);

        // Le même message est affiché que l'adresse existe ou non : indiquer
        // le contraire révélerait quels comptes existent.
        return back()->with('status', __('passwords.sent'));
    }

    public function resetForm(Request $request, string $token): View
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:180'],
            'password' => ['required', 'confirmed', PasswordRule::defaults()],
        ], attributes: [
            'email' => __('auth.fields.email'),
            'password' => __('auth.fields.password'),
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                    'password_changed_at' => now(),
                ])->save();

                event(new PasswordReset($user));

                Log::channel('auth')->notice('Mot de passe réinitialisé.', [
                    'user_id' => $user->getKey(),
                ]);
            },
        );

        if ($status !== Password::PasswordReset) {
            throw ValidationException::withMessages([
                'email' => __($status),
            ]);
        }

        return redirect()
            ->route('admin.login')
            ->with('status', __($status));
    }
}
