@extends('layouts.print')

@php
    $settings = app(\App\Services\SettingsService::class);

    $publicOffers = ['permanence', 'workshops', 'scam_prevention', 'staff'];
    $businessOffers = ['ai_awareness', 'cybersecurity', 'ai_discovery', 'diagnosis'];
@endphp

@section('title', 'Interventions pour les communes, associations et entreprises')

@section('content')
    <div class="brochure">

        {{-- ============ En-tête ============ --}}
        <header class="brochure__header">
            {{--
                Le logo couleur : ce document part le plus souvent par
                courriel, et se lit à l'écran avant d'être imprimé.
            --}}
            <img
                class="brochure__logo"
                src="{{ \App\Support\Branding::logo() }}"
                alt=""
                role="presentation"
            >

            <div>
                <h1 class="brochure__title">{{ $settings->string('site_name') }}</h1>

                <p class="brochure__subtitle">
                    {{ $settings->string('site_tagline') }}@if ($settings->string('adviser_name')) — {{ $settings->string('adviser_name') }}@endif
                </p>
            </div>
        </header>

        <p class="brochure__lead">
            J’accompagne les habitants dans l’usage de l’informatique, d’internet, du
            téléphone et des démarches en ligne. J’interviens sur site, pour vos
            administrés, vos adhérents ou vos équipes.
        </p>

        {{-- ============ Collectivités ============ --}}
        <section class="brochure__section" aria-labelledby="brochure-public">
            <h2 class="brochure__section-title" id="brochure-public">
                {{ __('site.partnership.public_title') }}
            </h2>

            <p class="brochure__section-intro">{{ __('site.partnership.public_intro') }}</p>

            <ul class="brochure__offers" role="list">
                @foreach ($publicOffers as $key)
                    <li class="brochure__offer">
                        <h3 class="brochure__offer-title">
                            {{ __("site.partnership.public_offers.{$key}.title") }}
                        </h3>

                        <p class="brochure__offer-duration">
                            {{ __("site.partnership.public_offers.{$key}.duration") }}
                        </p>

                        <p class="brochure__offer-text">
                            {{ __("site.partnership.public_offers.{$key}.text") }}
                        </p>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- ============ Entreprises ============ --}}
        <section class="brochure__section" aria-labelledby="brochure-business">
            <h2 class="brochure__section-title" id="brochure-business">
                {{ __('site.partnership.business_title') }}
            </h2>

            <p class="brochure__section-intro">{{ __('site.partnership.business_intro') }}</p>

            <ul class="brochure__offers" role="list">
                @foreach ($businessOffers as $key)
                    <li class="brochure__offer brochure__offer--business">
                        <h3 class="brochure__offer-title">
                            {{ __("site.partnership.business_offers.{$key}.title") }}
                        </h3>

                        <p class="brochure__offer-duration">
                            {{ __("site.partnership.business_offers.{$key}.duration") }}
                        </p>

                        <p class="brochure__offer-text">
                            {{ __("site.partnership.business_offers.{$key}.text") }}
                        </p>
                    </li>
                @endforeach
            </ul>
        </section>

        {{-- ============ Modalités ============ --}}
        <section class="brochure__section" aria-labelledby="brochure-format">
            <h2 class="brochure__section-title" id="brochure-format">
                {{ __('site.partnership.format_title') }}
            </h2>

            <ul class="brochure__notes" role="list">
                @foreach (['onsite', 'group', 'materials', 'quote'] as $key)
                    <li>{{ __("site.partnership.format.{$key}") }}</li>
                @endforeach
            </ul>
        </section>

        {{-- ============ Secteur ============ --}}
        @if ($municipalities->isNotEmpty())
            <section class="brochure__section" aria-labelledby="brochure-secteur">
                <h2 class="brochure__section-title" id="brochure-secteur">Secteur d’intervention</h2>

                <p class="brochure__coverage">
                    {{ $municipalities->pluck('name')->implode(', ') }}, et communes limitrophes.
                </p>
            </section>
        @endif

        {{-- ============ Contact ============ --}}
        <section class="brochure__contact" aria-labelledby="brochure-contact">
            <h2 class="brochure__contact-title" id="brochure-contact">Me contacter</h2>

            <div class="brochure__contact-grid">
                <div>
                    @if ($settings->hasPhone())
                        <p>
                            <a class="brochure__phone" href="{{ $settings->phoneLink() }}">
                                {{ $settings->phoneDisplay() }}
                            </a>
                        </p>
                    @endif

                    @if ($settings->string('email'))
                        <p>{{ $settings->string('email') }}</p>
                    @endif

                    <p>{{ config('app.url') }}</p>
                </div>

                <div>
                    @if ($settings->string('city'))
                        <p>
                            {{ collect([
                                $settings->string('address'),
                                trim($settings->string('postal_code').' '.$settings->string('city')),
                            ])->filter()->implode(', ') }}
                        </p>
                    @endif

                    @if ($settings->string('legal_status'))
                        <p>{{ $settings->string('legal_status') }}</p>
                    @endif

                    @if ($settings->string('siret'))
                        <p>SIRET {{ $settings->string('siret') }}</p>
                    @endif
                </div>
            </div>
        </section>

        <p class="brochure__legal">
            Document établi le {{ \App\Support\FrenchFormat::shortDate(now()) }}.
            Un devis détaillé est adressé avant toute intervention, sans engagement.
        </p>
    </div>
@endsection
