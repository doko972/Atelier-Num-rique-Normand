<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Profil du compte connecté : coordonnées, mot de passe, sessions.
 */
class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('admin.profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $this->authorize('updateProfile', $user);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:180', Rule::unique('users', 'email')->ignore($user->getKey())],
            'phone' => ['nullable', 'string', 'max:30'],
        ], attributes: __('admin.users.attributes'));

        $emailChanged = $data['email'] !== $user->email;

        $user->fill($data);

        // Changer d'adresse impose de la revérifier.
        if ($emailChanged) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($emailChanged) {
            $user->sendEmailVerificationNotification();
        }

        return back()
            ->with('status', __('admin.profile.updated'))
            ->with('status_variant', 'success');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $user = $request->user();

        $this->authorize('updateProfile', $user);

        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ], attributes: [
            'current_password' => 'le mot de passe actuel',
            'password' => 'le nouveau mot de passe',
        ]);

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'password_changed_at' => now(),
        ])->save();

        Log::channel('auth')->notice('Mot de passe modifié depuis le profil.', [
            'user_id' => $user->getKey(),
        ]);

        return back()
            ->with('status', __('admin.profile.password_updated'))
            ->with('status_variant', 'success');
    }

    /**
     * Révoque toutes les autres sessions du compte (codex §23).
     */
    public function revokeSessions(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ], attributes: ['password' => 'le mot de passe']);

        Auth::logoutOtherDevices($request->string('password')->value());

        Log::channel('auth')->notice('Sessions révoquées.', [
            'user_id' => $request->user()->getKey(),
        ]);

        return back()
            ->with('status', __('admin.profile.sessions_revoked'))
            ->with('status_variant', 'success');
    }
}
