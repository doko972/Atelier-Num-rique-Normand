@extends('layouts.auth')

@section('title', __('auth.verify.title'))

@section('content')
    <h1 class="auth-card__title">{{ __('auth.verify.title') }}</h1>
    <p>{{ __('auth.verify.intro') }}</p>

    @if (session('status'))
        <x-alert variant="success" style="margin-top: 1rem">
            <p>{{ session('status') }}</p>
        </x-alert>
    @endif

    <div class="form__actions">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn--primary">{{ __('auth.verify.resend') }}</button>
        </form>

        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="btn btn--ghost">{{ __('auth.verify.logout') }}</button>
        </form>
    </div>
@endsection
