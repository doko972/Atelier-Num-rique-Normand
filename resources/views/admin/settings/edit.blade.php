@extends('layouts.admin')

@section('title', __('admin.settings.title'))

@section('content')
    <div class="admin-page-header">
        <div>
            <h1>{{ __('admin.settings.title') }}</h1>
            <p>{{ __('admin.settings.intro') }}</p>
        </div>
    </div>

    <div class="admin-panel">
        <x-form-errors />

        <form method="POST" action="{{ route('admin.settings.update') }}" class="form">
            @csrf
            @method('PUT')

            @foreach ($groups as $group => $settings)
                <fieldset class="fieldset">
                    <legend class="fieldset__legend">
                        {{ __("admin.settings.groups.{$group}") }}
                    </legend>

                    @foreach ($settings as $setting)
                        @if ($setting->type === 'boolean')
                            <div class="field">
                                <x-checkbox
                                    :name="'settings['.$setting->key.']'"
                                    :label="$setting->label"
                                    :description="$setting->help"
                                    :checked="(bool) $setting->typedValue()"
                                />
                            </div>
                        @else
                            <x-field
                                :name="'settings['.$setting->key.']'"
                                :type="$setting->type === 'text' ? 'textarea' : ($setting->type === 'integer' ? 'number' : 'text')"
                                :label="$setting->label"
                                :value="$setting->value"
                                :help="$setting->help"
                                :rows="4"
                            />
                        @endif
                    @endforeach
                </fieldset>
            @endforeach

            <div class="form__actions">
                <button type="submit" class="btn btn--primary btn--lg">{{ __('admin.common.save') }}</button>
            </div>
        </form>
    </div>

    <div class="admin-panel">
        <h2 class="admin-panel__title">{{ __('admin.settings.hours_title') }}</h2>
        <p class="text-small text-muted">{{ __('admin.settings.hours_intro') }}</p>

        <form method="POST" action="{{ route('admin.settings.hours') }}" class="form">
            @csrf
            @method('PUT')

            @php
                $byWeekday = $openingHours->keyBy('weekday');
                $days = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'];
            @endphp

            @foreach ($days as $weekday => $dayName)
                @php $hour = $byWeekday->get($weekday); @endphp

                <fieldset class="fieldset">
                    <legend class="fieldset__legend">{{ $dayName }}</legend>

                    <input type="hidden" name="hours[{{ $weekday }}][weekday]" value="{{ $weekday }}">

                    <x-field
                        :name="'hours['.$weekday.'][opens_at]'"
                        type="time"
                        label="Ouverture"
                        :value="$hour && $hour->opens_at ? \Carbon\CarbonImmutable::parse($hour->opens_at)->format('H:i') : null"
                    />

                    <x-field
                        :name="'hours['.$weekday.'][closes_at]'"
                        type="time"
                        label="Fermeture"
                        :value="$hour && $hour->closes_at ? \Carbon\CarbonImmutable::parse($hour->closes_at)->format('H:i') : null"
                    />

                    <x-checkbox
                        :name="'hours['.$weekday.'][is_closed]'"
                        :label="__('admin.settings.closed')"
                        :checked="$hour?->is_closed ?? true"
                    />

                    <x-field
                        :name="'hours['.$weekday.'][note]'"
                        label="Précision"
                        :value="$hour?->note"
                    />
                </fieldset>
            @endforeach

            <div class="form__actions">
                <button type="submit" class="btn btn--primary btn--lg">{{ __('admin.common.save') }}</button>
            </div>
        </form>
    </div>
@endsection
