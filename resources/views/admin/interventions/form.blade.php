@extends('layouts.admin')

@php
    $title = $isNew
        ? __('admin.interventions.create')
        : __('admin.interventions.edit', ['reference' => $intervention->reference]);
@endphp

@section('title', $title)

@section('content')
    <div class="admin-page-header">
        <div>
            <h1>{{ $title }}</h1>

            @if ($appointment)
                <p>{{ __('admin.interventions.from_appointment', ['reference' => $appointment->reference]) }}</p>
            @endif
        </div>

        <a class="btn btn--ghost" href="{{ $appointment && ! $appointment->trashed() ? route('admin.appointments.show', $appointment) : route('admin.interventions.index') }}">
            {{ __('admin.common.back') }}
        </a>
    </div>

    <div class="admin-panel">
        <x-form-errors />

        <p class="form__required-note">{{ __('admin.common.required_fields') }}</p>

        <form
            method="POST"
            action="{{ $isNew ? route('admin.interventions.store') : route('admin.interventions.update', $intervention) }}"
            class="form"
        >
            @csrf
            @unless ($isNew)
                @method('PUT')
            @endunless

            @if ($intervention->appointment_id)
                <input type="hidden" name="appointment_id" value="{{ $intervention->appointment_id }}">
            @endif

            <x-field
                name="activity"
                type="select"
                :label="__('admin.interventions.fields.activity')"
                :help="__('admin.interventions.fields.activity_help')"
                :options="\App\Enums\InterventionActivity::groupedOptions()"
                :value="$intervention->activity?->value"
                required
            />

            <x-field
                name="performed_on"
                type="date"
                :label="__('admin.interventions.fields.performed_on')"
                :value="$intervention->performed_on?->format('Y-m-d')"
                :max="today()->format('Y-m-d')"
                required
            />

            <x-field
                name="client_name"
                :label="__('admin.interventions.fields.client_name')"
                :help="__('admin.interventions.fields.client_name_help')"
                :value="$intervention->client_name"
                maxlength="150"
                required
            />

            <x-field
                name="duration_minutes"
                type="number"
                :label="__('admin.interventions.fields.duration_minutes')"
                :value="$intervention->duration_minutes"
                min="1"
                max="1440"
                step="5"
                required
            />

            <x-field
                name="amount"
                :label="__('admin.interventions.fields.amount')"
                :help="__('admin.interventions.fields.amount_help')"
                :value="$intervention->amountForInput()"
                inputmode="decimal"
                required
            />

            <x-field
                name="invoice_number"
                :label="__('admin.interventions.fields.invoice_number')"
                :value="$intervention->invoice_number"
                maxlength="50"
            />

            <x-field
                name="notes"
                type="textarea"
                :label="__('admin.interventions.fields.notes')"
                :value="$intervention->notes"
                :rows="3"
                maxlength="2000"
            />

            <div class="form__actions">
                <button type="submit" class="btn btn--primary btn--lg">{{ __('admin.common.save') }}</button>

                <a class="btn btn--ghost" href="{{ route('admin.interventions.index') }}">{{ __('admin.common.cancel') }}</a>
            </div>
        </form>
    </div>
@endsection
