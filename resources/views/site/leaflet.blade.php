@extends('layouts.print')

@php $settings = app(\App\Services\SettingsService::class); @endphp

@section('title', 'Plaquette à imprimer — '.$settings->string('site_name'))

{{-- La feuille adopte la largeur d'une A5, format de la plaquette. --}}
@section('page-format', 'a5')

@section('content')
    {{--
        Plaquette grand public, au format A5.

        Le contenu est volontairement court : une plaquette se lit debout,
        parfois sans lunettes, dans une salle d'attente. Tout ce qui n'est pas
        indispensable a été retiré. Le numéro de téléphone est l'élément le
        plus gros de la page, car c'est la seule action attendue.
    --}}
    <div class="leaflet">
        <div class="leaflet__header">
            {{--
                Version noire : la plaquette est imprimée chez soi, en noir et
                blanc. En niveaux de gris, le vert et le beige du logo couleur
                deviennent des gris pâles à peine visibles.
            --}}
            <img
                class="leaflet__logo"
                src="{{ \App\Support\Branding::logo(\App\Support\Branding::MONO_DARK) }}"
                alt=""
                role="presentation"
            >

            <div>
                <p class="leaflet__name">{{ $settings->string('site_name') }}</p>
                <p class="leaflet__tagline">
                    {{ $settings->string('site_tagline') }}@if ($settings->string('adviser_name'))<br>{{ $settings->string('adviser_name') }}@endif
                </p>
            </div>
        </div>

        <p class="leaflet__claim">
            Besoin d’aide avec un ordinateur,<br>
            un téléphone ou une démarche en ligne ?
        </p>

        <ul class="leaflet__list" role="list">
            <li>Apprendre à utiliser un ordinateur</li>
            <li>Se servir d’un téléphone ou d’une tablette</li>
            <li>Créer et utiliser une adresse électronique</li>
            <li>Faire une démarche administrative en ligne</li>
            <li>Reconnaître les arnaques</li>
            <li>Envoyer des photos à ses proches</li>
            <li>Faire un appel vidéo avec la famille</li>
            <li>Classer et sauvegarder ses documents</li>
        </ul>

        @if ($settings->hasPhone())
            <div class="leaflet__phone">
                <span class="leaflet__phone-label">Appelez-moi, c’est le plus simple</span>

                <a class="leaflet__phone-number" href="{{ $settings->phoneLink() }}">
                    {{ $settings->phoneDisplay() }}
                </a>

                @if ($openingHours->isNotEmpty())
                    <span class="leaflet__hours">
                        @foreach ($openingHours->where('is_closed', false) as $hour)
                            {{ ucfirst($hour->weekdayName()) }}@if (! $loop->last), @endif
                        @endforeach
                        — {{ $openingHours->firstWhere('is_closed', false)?->range() }}
                    </span>
                @endif
            </div>
        @endif

        <p style="font-size: 9.5pt; text-align: center; margin: 0">
            À votre rythme, sans jugement, et sans jargon.<br>
            <strong>À votre domicile ou près de chez vous.</strong>
        </p>

        <div class="leaflet__footer">
            @if ($municipalities->isNotEmpty())
                <p class="leaflet__coverage">
                    <strong>Je me déplace à :</strong>
                    {{ $municipalities->pluck('name')->implode(', ') }}, et alentour.
                </p>
            @endif

            <p class="leaflet__legal">
                {{ config('app.url') }}
                @if ($settings->string('email')) — {{ $settings->string('email') }} @endif
                <br>
                @if ($settings->string('legal_status')){{ $settings->string('legal_status') }} @endif
                @if ($settings->string('siret'))— SIRET {{ $settings->string('siret') }}@endif
            </p>
        </div>
    </div>

@endsection

{{-- Hors de la feuille : ces consignes ne doivent pas être imprimées. --}}
@section('after-sheet')
    <div class="callout">
        <h2 class="callout__title">Comment l’imprimer</h2>

        <ol>
            <li>Cliquez sur « Imprimer cette fiche » ci-dessus.</li>
            <li>Dans la fenêtre d’impression, choisissez <strong>2 pages par feuille</strong>.</li>
            <li>Coupez la feuille en deux : vous obtenez deux plaquettes.</li>
        </ol>

        <p class="text-small">
            La mise en page est prévue pour rester lisible imprimée en noir et blanc.
        </p>
    </div>
@endsection
