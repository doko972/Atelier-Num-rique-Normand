@extends('layouts.site')

@php $waiting = ! $workshop->registrationsOpen(); @endphp

@section('title', 'Inscription — '.$workshop->title)
@section('noindex', true)

@section('breadcrumb')
    <x-breadcrumb :items="[
        __('site.workshops.title') => route('workshops.index'),
        $workshop->title => route('workshops.show', $workshop),
        'Inscription' => null,
    ]" />
@endsection

@section('content')
    <div class="page-header">
        <div class="container">
            <h1>{{ $waiting ? __('site.workshops.register_waiting') : __('site.workshops.register') }}</h1>
            <p>{{ $workshop->title }} — {{ $workshop->date->locale('fr')->isoFormat('dddd D MMMM YYYY') }}</p>
        </div>
    </div>

    <section class="section">
        <div class="container container--narrow">
            <x-form-errors />

            @if ($waiting)
                <x-alert variant="warning" :title="__('site.workshops.full')">
                    <p>{{ __('site.workshops.waiting_list_open') }}</p>
                </x-alert>
            @endif

            <p class="form__required-note">{{ __('site.common.required_fields') }}</p>

            <form
                method="POST"
                action="{{ route('workshops.register.store', $workshop) }}"
                class="form"
                data-guard-submit
            >
                @csrf
                <x-honeypot />

                <fieldset class="fieldset">
                    <legend class="fieldset__legend">Vos coordonnées</legend>

                    <x-field name="first_name" label="Votre prénom" required autocomplete="given-name" />
                    <x-field name="last_name" label="Votre nom" required autocomplete="family-name" />

                    <x-field
                        name="phone"
                        type="tel"
                        label="Votre numéro de téléphone"
                        required
                        autocomplete="tel"
                        placeholder="06 12 34 56 78"
                        help="C’est par ce numéro que je vous préviendrai en cas de changement."
                    />

                    <x-field
                        name="email"
                        type="email"
                        label="Votre adresse électronique"
                        autocomplete="email"
                        help="Si vous en avez une, vous recevrez une confirmation écrite. Sinon, ce n’est pas grave."
                    />

                    <x-field
                        name="municipality_id"
                        type="select"
                        label="Votre commune"
                        :options="$municipalities->pluck('name', 'id')->all()"
                        empty-option="— Choisissez votre commune —"
                    />
                </fieldset>

                <fieldset class="fieldset">
                    <legend class="fieldset__legend">Pour bien vous accueillir</legend>

                    <x-field
                        name="age_range"
                        type="select"
                        label="Votre tranche d’âge"
                        :options="\App\Enums\AgeRange::options()"
                        empty-option="— Je préfère ne pas répondre —"
                        help="Cette information sert uniquement aux bilans anonymes remis aux communes."
                    />

                    <x-field
                        name="device"
                        type="select"
                        label="L’appareil que vous utiliserez"
                        :options="\App\Enums\DeviceType::options()"
                        empty-option="— Je ne sais pas encore —"
                    />

                    <x-field
                        name="special_needs"
                        type="textarea"
                        label="Un besoin particulier ?"
                        :rows="4"
                        maxlength="1000"
                        help="Difficulté à vous déplacer, à entendre, à voir l’écran… Dites-le-moi, je m’adapterai."
                    />

                    <x-checkbox
                        name="voice_message_allowed"
                        :label="__('consent.labels.voice_message')"
                        description="Si vous ne répondez pas, je laisserai un message avec mon nom et mon numéro."
                    />
                </fieldset>

                <fieldset class="fieldset">
                    <legend class="fieldset__legend">Vos données</legend>

                    <x-checkbox
                        name="consent"
                        :label="__('consent.labels.workshop_registration')"
                        :description="__('consent.statements.workshop_registration')"
                        required
                    />

                    <p class="text-small text-muted" style="margin-top: 0.75rem">
                        {{ __('consent.retention_notice') }}
                        <a href="{{ route('privacy') }}">{{ __('consent.privacy_link') }}</a>
                    </p>
                </fieldset>

                <div class="form__actions">
                    <button type="submit" class="btn btn--accent btn--lg" data-submitting-label="{{ __('site.common.sending') }}">
                        {{ $waiting ? __('site.workshops.register_waiting') : __('site.workshops.register') }}
                    </button>

                    <a class="btn btn--ghost" href="{{ route('workshops.show', $workshop) }}">
                        {{ __('site.common.back') }}
                    </a>
                </div>
            </form>

            <x-phone-cta />
        </div>
    </section>
@endsection
