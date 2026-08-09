@extends('layouts.auth')

@section('title', __('auth.reset.title'))

@section('content')
    <h1 class="auth-card__title">{{ __('auth.reset.title') }}</h1>
    <p class="text-muted">{{ __('auth.reset.intro') }}</p>

    <x-form-errors />

    <form method="POST" action="{{ route('password.update') }}" style="margin-top: 1.5rem">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

        <x-field
            name="email"
            type="email"
            label="Adresse électronique"
            :value="$email"
            required
            autocomplete="username"
        />

        <x-field
            name="password"
            type="password"
            label="Nouveau mot de passe"
            required
            autocomplete="new-password"
            help="Au moins douze caractères, différents de ceux utilisés ailleurs."
        />

        <x-field
            name="password_confirmation"
            type="password"
            :label="__('auth.reset.confirmation')"
            required
            autocomplete="new-password"
        />

        <div class="form__actions">
            <button type="submit" class="btn btn--primary btn--lg btn--block">
                {{ __('auth.reset.submit') }}
            </button>
        </div>
    </form>
@endsection
