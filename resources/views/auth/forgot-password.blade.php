@extends('layouts.auth')

@section('title', __('auth.forgot.title'))

@section('content')
    <h1 class="auth-card__title">{{ __('auth.forgot.title') }}</h1>
    <p class="text-muted">{{ __('auth.forgot.intro') }}</p>

    @if (session('status'))
        <x-alert variant="success" style="margin-top: 1rem">
            <p>{{ session('status') }}</p>
        </x-alert>
    @endif

    <x-form-errors />

    <form method="POST" action="{{ route('password.email') }}" style="margin-top: 1.5rem">
        @csrf

        <x-field
            name="email"
            type="email"
            label="Adresse électronique"
            required
            autocomplete="username"
            autofocus
        />

        <div class="form__actions">
            <button type="submit" class="btn btn--primary btn--lg btn--block">
                {{ __('auth.forgot.submit') }}
            </button>
        </div>
    </form>

    <p style="margin-top: 1.5rem">
        <a href="{{ route('admin.login') }}">{{ __('auth.forgot.back') }}</a>
    </p>
@endsection
