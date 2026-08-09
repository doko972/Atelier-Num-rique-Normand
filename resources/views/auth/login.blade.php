@extends('layouts.auth')

@section('title', __('auth.login.title'))

@section('content')
    <h1 class="auth-card__title">{{ __('auth.login.title') }}</h1>
    <p class="text-muted">{{ __('auth.login.intro') }}</p>

    @if (session('status'))
        <x-alert variant="success" style="margin-top: 1rem">
            <p>{{ session('status') }}</p>
        </x-alert>
    @endif

    <x-form-errors />

    <form method="POST" action="{{ route('admin.login.store') }}" style="margin-top: 1.5rem">
        @csrf

        <x-field
            name="email"
            type="email"
            label="Adresse électronique"
            required
            autocomplete="username"
            autofocus
        />

        <x-field
            name="password"
            type="password"
            label="Mot de passe"
            required
            autocomplete="current-password"
        />

        <x-checkbox name="remember" :label="__('auth.login.remember')" />

        <div class="form__actions">
            <button type="submit" class="btn btn--primary btn--lg btn--block">
                {{ __('auth.login.submit') }}
            </button>
        </div>
    </form>

    <p style="margin-top: 1.5rem">
        <a href="{{ route('password.request') }}">{{ __('auth.login.forgot') }}</a>
    </p>

    <p>
        <a href="{{ route('home') }}">{{ __('auth.login.back_to_site') }}</a>
    </p>
@endsection
