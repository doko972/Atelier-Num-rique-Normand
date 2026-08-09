@extends('layouts.site')

@php $settings = app(\App\Services\SettingsService::class); @endphp

{{--
    La description destinée aux moteurs est un champ distinct du sous-titre
    affiché : l'une doit nommer le métier et la commune, l'autre s'adresser
    à la personne qui vient d'arriver. Les confondre obligeait à sacrifier
    l'une des deux.
--}}
@section('title', $settings->string('site_tagline'))
@section('meta_description', $settings->string('meta_description'))

@push('head')
    {{-- Données structurées : service local (codex §29) --}}
    <x-structured-data :data="\App\Support\StructuredData::professionalService($settings, $municipalities)" />
@endpush

@section('content')

    {{-- ===================== Héro ===================== --}}
    <section class="hero">
        <div class="hero__inner">
            <div>
                <h1 class="hero__title">{{ $settings->string('home_hero_title') }}</h1>

                <p class="hero__subtitle">{{ $settings->string('home_hero_subtitle') }}</p>

                <div class="hero__actions">
                    <a class="btn btn--accent btn--lg" href="{{ route('appointments.create') }}">
                        {{ __('site.cta.appointment') }}
                    </a>

                    <a class="btn btn--primary btn--lg" href="{{ route('workshops.index') }}">
                        {{ __('site.cta.workshops') }}
                    </a>

                    @if ($settings->hasPhone())
                        <a class="btn btn--outline btn--lg" href="{{ $settings->phoneLink() }}">
                            <x-icon name="telephone" class="btn__icon" />
                            {{ $settings->phoneDisplay() }}
                        </a>
                    @endif
                </div>

                @if ($settings->hasPhone())
                    <p class="hero__note">
                        @if ($settings->isOpenAt())
                            {{ __('site.call.open') }}
                        @else
                            {{ __('site.call.closed') }} {{ $settings->string('closed_message') }}
                        @endif
                    </p>
                @endif
            </div>

            <div>
                <blockquote>
                    <p>
                        Le numérique ne doit laisser personne de côté.
                        Je vous accompagne pas à pas, à votre rythme, près de chez vous.
                    </p>
                </blockquote>
            </div>
        </div>
    </section>

    {{-- ===================== Réassurance ===================== --}}
    <section class="section section--surface">
        <div class="container">
            <div class="section__header section__header--center">
                <h2>{{ __('site.home.pledges_title') }}</h2>
            </div>

            <ul class="grid" role="list">
                @foreach ([
                    'pace' => 'horloge',
                    'no_judgement' => 'coeur',
                    'nearby' => 'lieu',
                    'privacy' => 'securite',
                ] as $key => $icon)
                    <li class="pledge">
                        <x-icon :name="$icon" class="pledge__icon" />
                        <h3 class="pledge__title">{{ __("site.home.pledges.{$key}.title") }}</h3>
                        <p class="pledge__text">{{ __("site.home.pledges.{$key}.text") }}</p>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>

    {{-- ===================== Services ===================== --}}
    @if ($services->isNotEmpty())
        <section class="section" aria-labelledby="titre-services">
            <div class="container">
                <div class="section__header">
                    <h2 id="titre-services">{{ __('site.home.services_title') }}</h2>
                    <p>{{ __('site.home.services_intro') }}</p>
                </div>

                <ul class="home-services__grid" role="list">
                    @foreach ($services as $service)
                        <li class="card card--linked">
                            <div class="card__body">
                                <x-icon :name="$service->icon ?: 'aide'" class="card__icon" />

                                <h3 class="card__title">
                                    <a class="card__link" href="{{ route('services.detail', [$service->category, $service]) }}">
                                        {{ $service->title }}
                                    </a>
                                </h3>

                                <p>{{ $service->summary }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>

                <p class="section__footer">
                    <a class="btn btn--outline btn--lg" href="{{ route('services.index') }}">
                        {{ __('site.home.services_all') }}
                    </a>
                </p>
            </div>
        </section>
    @endif

    {{-- ===================== Fonctionnement ===================== --}}
    <section class="section section--primary" aria-labelledby="titre-fonctionnement">
        <div class="container">
            <div class="section__header section__header--center">
                <h2 id="titre-fonctionnement">{{ __('site.home.how_title') }}</h2>
            </div>

            <ol class="steps">
                @foreach (['step1', 'step2', 'step3'] as $step)
                    <li class="steps__item">
                        <h3 class="steps__title">{{ __("site.home.how.{$step}.title") }}</h3>
                        <p>{{ __("site.home.how.{$step}.text") }}</p>
                    </li>
                @endforeach
            </ol>
        </div>
    </section>

    {{-- ===================== Ateliers ===================== --}}
    <section class="section section--surface" aria-labelledby="titre-ateliers">
        <div class="container">
            <div class="section__header">
                <h2 id="titre-ateliers">{{ __('site.home.workshops_title') }}</h2>
                <p>{{ __('site.home.workshops_intro') }}</p>
            </div>

            @if ($workshops->isEmpty())
                <p>{{ __('site.home.workshops_empty') }}</p>
            @else
                <div class="stack">
                    @foreach ($workshops as $workshop)
                        <x-workshop-card :workshop="$workshop" />
                    @endforeach
                </div>

                <p class="section__footer">
                    <a class="btn btn--outline btn--lg" href="{{ route('workshops.index') }}">
                        {{ __('site.home.workshops_all') }}
                    </a>
                </p>
            @endif
        </div>
    </section>

    {{-- ===================== Accompagnement à domicile ===================== --}}
    <section class="section" aria-labelledby="titre-domicile">
        <div class="container">
            <div class="split">
                <div class="stack">
                    <h2 id="titre-domicile">{{ __('site.home.home_visit_title') }}</h2>
                    <p>{{ __('site.home.home_visit_text') }}</p>

                    @if ($settings->string('home_visit_area'))
                        <p>{{ $settings->string('home_visit_area') }}</p>
                    @endif

                    <p>
                        <a class="btn btn--accent btn--lg" href="{{ route('appointments.create') }}">
                            {{ __('site.cta.appointment') }}
                        </a>
                    </p>
                </div>

                @if ($municipalities->isNotEmpty())
                    <div class="stack">
                        <h3>{{ __('site.home.coverage_title') }}</h3>

                        <ul class="coverage" role="list">
                            @foreach ($municipalities as $municipality)
                                <li class="coverage__item">{{ $municipality->name }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </section>

    {{-- ===================== Témoignages ===================== --}}
    @if ($testimonials->isNotEmpty())
        <section class="section section--sunken" aria-labelledby="titre-temoignages">
            <div class="container">
                <div class="section__header section__header--center">
                    <h2 id="titre-temoignages">{{ __('site.home.testimonials_title') }}</h2>
                </div>

                <ul class="grid" role="list">
                    @foreach ($testimonials as $testimonial)
                        <li>
                            <figure class="testimonial">
                                <blockquote class="testimonial__quote" style="border: 0; background: none; padding: 0">
                                    {{ $testimonial->quote }}
                                </blockquote>
                                <figcaption class="testimonial__author">{{ $testimonial->attribution() }}</figcaption>
                            </figure>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- ===================== Partenaires ===================== --}}
    @if ($partners->isNotEmpty())
        <section class="section section--surface" aria-labelledby="titre-partenaires">
            <div class="container">
                <div class="section__header section__header--center">
                    <h2 id="titre-partenaires">{{ __('site.home.partners_title') }}</h2>
                </div>

                <ul class="partner-list" role="list">
                    @foreach ($partners as $partner)
                        <li class="partner-list__item">
                            @if ($partner->logo_path)
                                <img
                                    class="partner-list__logo"
                                    src="{{ \Illuminate\Support\Facades\Storage::url($partner->logo_path) }}"
                                    alt="{{ $partner->logo_alt ?: $partner->name }}"
                                    loading="lazy"
                                >
                            @else
                                {{ $partner->name }}
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    {{-- ===================== Dernier appel à l'action ===================== --}}
    <section class="home-cta">
        <div class="container">
            <h2>{{ __('site.cta.final_title') }}</h2>
            <p>{{ $settings->string('home_final_cta') }}</p>

            @if ($settings->hasPhone())
                <a class="home-phone" href="{{ $settings->phoneLink() }}">
                    <x-icon name="telephone" width="32" height="32" />
                    {{ $settings->phoneDisplay() }}
                </a>
            @endif

            <div class="home-cta__actions">
                <a class="btn btn--outline btn--lg" href="{{ route('appointments.create') }}">
                    {{ __('site.cta.appointment') }}
                </a>
                <a class="btn btn--outline btn--lg" href="{{ route('contact.create') }}">
                    {{ __('site.cta.contact') }}
                </a>
            </div>
        </div>
    </section>

@endsection
