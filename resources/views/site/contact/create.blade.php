@extends('layouts.site')

@php $settings = app(\App\Services\SettingsService::class); @endphp

@section('title', __('site.contact.title'))
@section('meta_description', __('site.contact.intro'))

@section('breadcrumb')
    <x-breadcrumb :items="[__('site.contact.title') => null]" />
@endsection

@section('content')
    <div class="page-header">
        <div class="container">
            <h1>{{ __('site.contact.title') }}</h1>
            <p>{{ __('site.contact.intro') }}</p>
        </div>
    </div>

    <section class="section">
        <div class="container">
            <div class="split--aside split">
                <div>
                    <x-form-errors />

                    <h2>{{ __('site.contact.form_title') }}</h2>

                    <p class="form__required-note">{{ __('site.common.required_fields') }}</p>

                    <form method="POST" action="{{ route('contact.store') }}" class="form" data-guard-submit>
                        @csrf
                        <x-honeypot />

                        <fieldset class="fieldset">
                            <legend class="fieldset__legend">Vos coordonnées</legend>

                            <x-field name="first_name" label="Votre prénom" required autocomplete="given-name" />
                            <x-field name="last_name" label="Votre nom" autocomplete="family-name" />

                            <x-field
                                name="phone"
                                type="tel"
                                label="Votre numéro de téléphone"
                                autocomplete="tel"
                                placeholder="06 12 34 56 78"
                                help="Indiquez au moins un téléphone ou une adresse électronique."
                            />

                            <x-field name="email" type="email" label="Votre adresse électronique" autocomplete="email" />

                            <x-field
                                name="municipality_id"
                                type="select"
                                label="Votre commune"
                                :options="$municipalities->pluck('name', 'id')->all()"
                                empty-option="— Choisissez votre commune —"
                            />

                            <x-radio-group
                                name="contact_preference"
                                legend="Comment préférez-vous ma réponse ?"
                                :options="\App\Enums\ContactPreference::options()"
                                :value="\App\Enums\ContactPreference::Phone->value"
                                required
                            />

                            <x-checkbox
                                name="voice_message_allowed"
                                :label="__('consent.labels.voice_message')"
                            />
                        </fieldset>

                        <fieldset class="fieldset">
                            <legend class="fieldset__legend">Votre message</legend>

                            <x-field name="subject" label="Sujet" required maxlength="200" />

                            <x-field
                                name="message"
                                type="textarea"
                                label="Votre message"
                                required
                                :rows="7"
                                maxlength="3000"
                                help="Écrivez comme vous parleriez : je comprendrai."
                            />
                        </fieldset>

                        <fieldset class="fieldset">
                            <legend class="fieldset__legend">Vos données</legend>

                            <x-checkbox
                                name="consent"
                                :label="__('consent.labels.contact_request')"
                                :description="__('consent.statements.contact_request')"
                                required
                            />

                            <p class="text-small text-muted" style="margin-top: 0.75rem">
                                {{ __('consent.retention_notice') }}
                                <a href="{{ route('privacy') }}">{{ __('consent.privacy_link') }}</a>
                            </p>
                        </fieldset>

                        <div class="form__actions">
                            <button type="submit" class="btn btn--accent btn--lg" data-submitting-label="{{ __('site.common.sending') }}">
                                {{ __('site.contact.submit') }}
                            </button>
                        </div>
                    </form>
                </div>

                <aside class="stack">
                    <x-phone-cta />

                    @if ($settings->openingHours()->isNotEmpty())
                        <div class="callout">
                            <h2 class="callout__title">{{ __('site.call.hours_title') }}</h2>

                            <ul role="list">
                                @foreach ($settings->openingHours() as $hour)
                                    <li>{{ ucfirst($hour->weekdayName()) }} : {{ $hour->range() }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($locations->isNotEmpty())
                        <div class="callout">
                            <h2 class="callout__title">{{ __('site.contact.locations_title') }}</h2>

                            <ul role="list" class="stack--sm">
                                @foreach ($locations as $location)
                                    <li>
                                        <strong>{{ $location->name }}</strong><br>
                                        {{ $location->fullAddress() }}<br>

                                        @if ($location->is_accessible)
                                            <x-badge variant="success">{{ __('site.contact.accessible') }}</x-badge>
                                        @endif

                                        <a href="{{ $location->mapUrl() }}" rel="noopener noreferrer" target="_blank">
                                            {{ __('site.contact.open_map') }}
                                            <span class="visually-hidden">({{ __('site.common.new_window') }})</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </aside>
            </div>
        </div>
    </section>
@endsection
