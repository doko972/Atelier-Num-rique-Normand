@extends('layouts.site')

@section('title', __('site.appointments.confirmation_title'))
@section('noindex', true)

@section('content')
    <section class="section">
        <div class="container container--narrow">
            <x-alert variant="success" :title="__('site.appointments.confirmation_title')">
                <p>{{ __('site.appointments.confirmation_reference', ['reference' => $reference]) }}</p>
                <p>{{ __('site.appointments.confirmation_next') }}</p>

                @if ($hasEmail)
                    <p>{{ __('site.appointments.confirmation_email') }}</p>
                @else
                    <p>{{ __('site.appointments.confirmation_no_email') }}</p>
                @endif
            </x-alert>

            <div class="btn-group" style="margin-top: 2rem">
                <a class="btn btn--primary btn--lg" href="{{ route('home') }}">
                    {{ __('site.nav.home') }}
                </a>
                <a class="btn btn--outline btn--lg" href="{{ route('workshops.index') }}">
                    {{ __('site.cta.workshops') }}
                </a>
            </div>

            <x-phone-cta />
        </div>
    </section>
@endsection
