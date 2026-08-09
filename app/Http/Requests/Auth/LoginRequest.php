<?php

declare(strict_types=1);

namespace App\Http\Requests\Auth;

use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Tentative de connexion, avec limitation du nombre d'essais (codex §23).
 */
class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:180'],
            'password' => ['required', 'string'],
            'remember' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Authentifie l'utilisateur, ou lève une erreur de validation.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $credentials = [
            ...$this->only('email', 'password'),
            // Un compte désactivé ou supprimé ne peut jamais se connecter,
            // même avec un mot de passe correct.
            'is_active' => true,
        ];

        if (! Auth::attempt($credentials, $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

            Log::channel('securite')->warning('Échec de connexion.', [
                'email' => $this->string('email')->value(),
                'ip' => $this->ip(),
            ]);

            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * @throws ValidationException
     */
    protected function ensureIsNotRateLimited(): void
    {
        $maxAttempts = (int) config('site.security.login.max_attempts');

        if (! RateLimiter::tooManyAttempts($this->throttleKey(), $maxAttempts)) {
            return;
        }

        Event::dispatch(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        Log::channel('securite')->warning('Connexion bloquée : trop de tentatives.', [
            'email' => $this->string('email')->value(),
            'ip' => $this->ip(),
        ]);

        throw ValidationException::withMessages([
            'email' => __('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Clé de limitation : couple adresse électronique / adresse IP.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(
            Str::lower($this->string('email')->value()).'|'.$this->ip(),
        );
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'email' => __('auth.fields.email'),
            'password' => __('auth.fields.password'),
        ];
    }
}
