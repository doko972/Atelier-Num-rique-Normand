@extends('layouts.site')

@section('title', __('site.appointments.title'))
@section('meta_description', __('site.appointments.intro'))

@section('breadcrumb')
    <x-breadcrumb :items="[__('site.appointments.title') => null]" />
@endsection

@section('content')
    <div class="page-header">
        <div class="container">
            <h1>{{ __('site.appointments.title') }}</h1>
            <p>{{ __('site.appointments.intro') }}</p>
        </div>
    </div>

    <section class="section">
        <div class="container container--narrow">
            <x-form-errors />

            <x-alert variant="info">
                <p>{{ __('site.appointments.no_email_note') }}</p>
            </x-alert>

            <p class="form__required-note">{{ __('site.common.required_fields') }}</p>

            <form method="POST" action="{{ route('appointments.store') }}" class="form" data-guard-submit>
                @csrf
                <x-honeypot />

                <fieldset class="fieldset">
                    <legend class="fieldset__legend">{{ __('site.appointments.type_title') }}</legend>

                    <x-radio-group
                        name="type"
                        legend="Quel type de rendez-vous souhaitez-vous ?"
                        :options="collect($types)->mapWithKeys(fn ($type) => [$type->value => $type->label()])->all()"
                        :descriptions="collect($types)->mapWithKeys(fn ($type) => [$type->value => $type->description()])->all()"
                        :value="\App\Enums\AppointmentType::Individual->value"
                        required
                    />
                </fieldset>

                <fieldset class="fieldset">
                    <legend class="fieldset__legend">{{ __('site.appointments.identity_title') }}</legend>

                    <x-field name="first_name" label="Votre prénom" required autocomplete="given-name" />
                    <x-field name="last_name" label="Votre nom" required autocomplete="family-name" />

                    <x-field
                        name="phone"
                        type="tel"
                        label="Votre numéro de téléphone"
                        required
                        autocomplete="tel"
                        placeholder="06 12 34 56 78"
                        help="Indiquez votre numéro pour que je puisse vous rappeler."
                    />

                    <x-field
                        name="email"
                        type="email"
                        label="Votre adresse électronique"
                        autocomplete="email"
                        help="Facultatif. Si vous n’en avez pas, je vous rappellerai par téléphone."
                    />

                    <x-field
                        name="municipality_id"
                        type="select"
                        label="Votre commune"
                        :options="$municipalities->pluck('name', 'id')->all()"
                        empty-option="— Choisissez votre commune —"
                    />

                    <x-radio-group
                        name="contact_preference"
                        legend="Comment préférez-vous être recontacté ?"
                        :options="\App\Enums\ContactPreference::options()"
                        :value="\App\Enums\ContactPreference::Phone->value"
                        required
                    />

                    <x-checkbox
                        name="voice_message_allowed"
                        :label="__('consent.labels.voice_message')"
                        description="Si vous ne répondez pas, je laisserai un message avec mon nom et mon numéro."
                    />
                </fieldset>

                <fieldset class="fieldset">
                    <legend class="fieldset__legend">{{ __('site.appointments.need_title') }}</legend>

                    <x-field
                        name="need_description"
                        type="textarea"
                        label="Expliquez ce qui vous pose problème"
                        required
                        :rows="6"
                        maxlength="2000"
                        help="Quelques phrases suffisent. Exemple : « Je n’arrive pas à ouvrir mes courriels sur ma tablette »."
                    />

                    <x-field
                        name="device"
                        type="select"
                        label="Quel appareil est concerné ?"
                        :options="\App\Enums\DeviceType::options()"
                        empty-option="— Je ne sais pas —"
                    />
                </fieldset>

                <fieldset class="fieldset">
                    <legend class="fieldset__legend">{{ __('site.appointments.availability_title') }}</legend>

                    <x-field
                        name="availability"
                        type="textarea"
                        label="Quand êtes-vous disponible ?"
                        :rows="3"
                        maxlength="500"
                        help="Exemple : « Les mardis et jeudis matin », ou « plutôt en fin de journée »."
                    />

                    <x-checkbox
                        name="home_visit_requested"
                        label="Je souhaite que vous veniez à mon domicile"
                    />

                    <x-checkbox
                        name="has_mobility_difficulty"
                        label="J’ai des difficultés à me déplacer"
                        description="Cela m’aide à choisir un lieu adapté, ou à venir chez vous."
                    />
                </fieldset>

                <fieldset class="fieldset">
                    <legend class="fieldset__legend">Vos données</legend>

                    <x-checkbox
                        name="consent"
                        :label="__('consent.labels.appointment_request')"
                        :description="__('consent.statements.appointment_request')"
                        required
                    />

                    <p class="text-small text-muted" style="margin-top: 0.75rem">
                        {{ __('consent.retention_notice') }}
                        <a href="{{ route('privacy') }}">{{ __('consent.privacy_link') }}</a>
                    </p>
                </fieldset>

                <div class="form__actions">
                    <button type="submit" class="btn btn--accent btn--lg" data-submitting-label="{{ __('site.common.sending') }}">
                        {{ __('site.appointments.submit') }}
                    </button>
                </div>
            </form>

            <x-phone-cta />
        </div>
    </section>
@endsection
