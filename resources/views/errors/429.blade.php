@extends('layouts.site')

@section('title', 'Trop d’envois en peu de temps')
@section('noindex', true)

@section('content')
    <section class="section">
        <div class="container container--narrow">
            <h1>Trop d’envois en peu de temps</h1>

            <x-alert variant="warning">
                <p>
                    Par sécurité, le site limite le nombre de formulaires envoyés depuis
                    une même connexion. Patientez quelques minutes, puis réessayez.
                </p>
                <p>Si c’est urgent, appelez-moi : je remplirai le formulaire avec vous.</p>
            </x-alert>

            <x-phone-cta />

            <p style="margin-top: 2rem">
                <a class="btn btn--primary" href="{{ route('home') }}">{{ __('site.nav.home') }}</a>
            </p>
        </div>
    </section>
@endsection
