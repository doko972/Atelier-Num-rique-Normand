{{--
    Bloc « appelez-moi ».

    Le téléphone est le canal principal du service (codex §35) : ce bloc est
    proposé à côté de chaque formulaire, pour que personne ne reste bloqué
    devant un champ qu'il ne comprend pas.
--}}
@props(['variant' => 'default'])

@php $settings = app(\App\Services\SettingsService::class); @endphp

@if ($settings->hasPhone())
    <div {{ $attributes->merge(['class' => 'form__phone-fallback']) }}>
        <h2 class="h4">{{ __('site.common.phone_fallback_title') }}</h2>

        <p>{{ __('site.common.phone_fallback_text') }}</p>

        <p>
            <a class="btn btn--secondary btn--lg" href="{{ $settings->phoneLink() }}">
                <x-icon name="telephone" class="btn__icon" />
                {{ __('site.call.label') }} {{ $settings->phoneDisplay() }}
            </a>
        </p>

        {{--
            Le numéro est répété en toutes lettres, et pas seulement porté par
            le lien : un lien `sms:` n'ouvre rien sur un ordinateur de bureau.
            Ainsi la personne peut le recopier sur son téléphone.
        --}}
        <p class="phone-cta__sms text-small">
            {{ __('site.call.sms_intro') }}
            {{ __('site.call.sms_action') }}
            <a href="{{ $settings->smsLink() }}">{{ $settings->phoneDisplay() }}</a>.
            {{ __('site.call.sms_promise') }}
        </p>

        @if ($settings->openingHours()->isNotEmpty())
            <p class="text-small text-muted">
                @if ($settings->isOpenAt())
                    {{ __('site.call.open') }}
                @else
                    {{ __('site.call.closed') }}
                    {{ $settings->string('closed_message') }}
                @endif
            </p>
        @endif
    </div>
@endif
