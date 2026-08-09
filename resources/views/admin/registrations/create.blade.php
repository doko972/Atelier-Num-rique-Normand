@extends('layouts.admin')

@section('title', __('admin.registrations.manual_title'))

@section('content')
    <div class="admin-page-header">
        <div>
            <h1>{{ __('admin.registrations.manual_title') }}</h1>
            <p>{{ __('admin.registrations.manual_intro') }}</p>
        </div>

        <a class="btn btn--ghost" href="{{ route('admin.workshops.participants', $workshop) }}">
            {{ __('admin.common.back') }}
        </a>
    </div>

    <div class="admin-panel">
        <p>
            <strong>{{ $workshop->title }}</strong> —
            {{ $workshop->date->locale('fr')->isoFormat('dddd D MMMM YYYY') }}
        </p>

        @php $remaining = $workshop->remainingSeats(); @endphp

        @if ($remaining === 0)
            <x-alert variant="warning">
                <p>
                    L’atelier est complet. La personne sera placée en liste d’attente
                    et prévenue dès qu’une place se libère.
                </p>
            </x-alert>
        @else
            <p class="text-muted">
                {{ trans_choice('site.workshops.seats_remaining', $remaining, ['count' => $remaining]) }}
            </p>
        @endif

        <x-form-errors />

        <form method="POST" action="{{ route('admin.registrations.store', $workshop) }}" class="form">
            @csrf

            <fieldset class="fieldset">
                <legend class="fieldset__legend">La personne</legend>

                <x-field name="first_name" label="Prénom" required />
                <x-field name="last_name" label="Nom" required />
                <x-field name="phone" type="tel" label="Téléphone" required placeholder="06 12 34 56 78" />
                <x-field name="email" type="email" label="Adresse électronique" help="Facultatif." />

                <x-field
                    name="municipality_id"
                    type="select"
                    label="Commune"
                    :options="$municipalities->pluck('name', 'id')->all()"
                    :empty-option="__('admin.common.none')"
                />

                <x-field name="municipality_name" label="Commune (si absente de la liste)" />
            </fieldset>

            <fieldset class="fieldset">
                <legend class="fieldset__legend">Informations complémentaires</legend>

                <x-field
                    name="age_range"
                    type="select"
                    label="Tranche d’âge"
                    :options="$ageRanges"
                    :empty-option="__('admin.common.none')"
                />

                <x-field
                    name="device"
                    type="select"
                    label="Appareil utilisé"
                    :options="$devices"
                    :empty-option="__('admin.common.none')"
                />

                <x-field name="special_needs" type="textarea" label="Besoin particulier" :rows="3" />

                <x-checkbox name="voice_message_allowed" :label="__('consent.labels.voice_message')" />
            </fieldset>

            <fieldset class="fieldset">
                <legend class="fieldset__legend">Consentement</legend>

                <x-checkbox
                    name="consent_confirmed"
                    :label="__('admin.registrations.consent_confirm')"
                    :description="__('consent.statements.workshop_registration')"
                    required
                />
            </fieldset>

            <div class="form__actions">
                <button type="submit" class="btn btn--primary btn--lg">{{ __('admin.common.save') }}</button>
                <a class="btn btn--ghost" href="{{ route('admin.workshops.participants', $workshop) }}">
                    {{ __('admin.common.cancel') }}
                </a>
            </div>
        </form>
    </div>
@endsection
