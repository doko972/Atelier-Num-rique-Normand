@extends('layouts.admin')

@section('title', __('admin.workshops.participants').' — '.$workshop->title)

@section('content')
    <div class="admin-page-header no-print">
        <div>
            <h1>{{ __('admin.workshops.participants') }}</h1>
            <p>
                {{ $workshop->title }} —
                {{ $workshop->date->locale('fr')->isoFormat('dddd D MMMM YYYY') }},
                {{ $workshop->startsAt()->format('H\\hi') }}
            </p>
        </div>

        <div class="btn-group">
            @can('create', \App\Models\WorkshopRegistration::class)
                <a class="btn btn--primary" href="{{ route('admin.registrations.create', $workshop) }}">
                    {{ __('admin.workshops.add_registration') }}
                </a>
            @endcan

            @can('export', \App\Models\Workshop::class)
                <a class="btn btn--outline" href="{{ route('admin.workshops.participants.export', $workshop) }}">
                    {{ __('admin.common.export') }}
                </a>
            @endcan

            <button type="button" class="btn btn--ghost" onclick="window.print()">
                {{ __('admin.common.print') }}
            </button>
        </div>
    </div>

    {{-- En-tête visible uniquement à l'impression : la liste papier doit se
         suffire à elle-même en salle. --}}
    <div class="printable">
        <h1 class="visually-hidden">{{ $workshop->title }}</h1>

        <div class="admin-panel">
            <p>
                <strong>{{ $workshop->title }}</strong><br>
                {{ $workshop->date->locale('fr')->isoFormat('dddd D MMMM YYYY') }},
                {{ $workshop->startsAt()->format('H\\hi') }} – {{ $workshop->endsAt()->format('H\\hi') }}<br>
                @if ($workshop->location)
                    {{ $workshop->location->name }}, {{ $workshop->location->fullAddress() }}
                @endif
            </p>

            <p>
                {{ __('admin.workshops.seats', [
                    'occupied' => $workshop->registrations()->occupyingSeat()->count(),
                    'capacity' => $workshop->capacity,
                ]) }}
            </p>

            @if ($registrations->isEmpty())
                <p class="table__empty">{{ __('admin.common.empty') }}</p>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <caption>Liste des participants</caption>

                        <thead>
                            <tr>
                                <th scope="col">Référence</th>
                                <th scope="col">Nom</th>
                                <th scope="col">Téléphone</th>
                                <th scope="col">Commune</th>
                                <th scope="col">Besoin particulier</th>
                                <th scope="col">Statut</th>
                                <th scope="col" class="no-print">{{ __('admin.common.actions') }}</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($registrations as $registration)
                                <tr>
                                    <td>{{ $registration->reference }}</td>

                                    <td>
                                        {{ $registration->fullName() }}
                                        @if ($registration->registered_by_phone)
                                            <br><span class="text-small text-muted">{{ __('admin.registrations.registered_by_phone') }}</span>
                                        @endif
                                    </td>

                                    <td>{{ $registration->phone }}</td>

                                    <td>{{ $registration->municipality?->name ?? $registration->municipality_name ?? '—' }}</td>

                                    <td>{{ $registration->special_needs ?: '—' }}</td>

                                    <td>
                                        <x-badge :variant="$registration->status->badgeVariant()">
                                            {{ $registration->status->label() }}
                                        </x-badge>

                                        @if ($registration->waiting_position)
                                            <br>
                                            <span class="text-small">
                                                {{ __('admin.registrations.waiting_position', ['position' => $registration->waiting_position]) }}
                                            </span>
                                        @endif
                                    </td>

                                    <td class="no-print">
                                        @can('update', $registration)
                                            <form method="POST" action="{{ route('admin.registrations.update', $registration) }}">
                                                @csrf
                                                @method('PUT')

                                                <label class="visually-hidden" for="statut-{{ $registration->id }}">
                                                    Statut de {{ $registration->fullName() }}
                                                </label>

                                                <select
                                                    class="field__control"
                                                    id="statut-{{ $registration->id }}"
                                                    name="status"
                                                    style="min-width: 12rem"
                                                >
                                                    <option value="{{ $registration->status->value }}">
                                                        {{ $registration->status->label() }}
                                                    </option>

                                                    @foreach ($registration->status->allowedTransitions() as $transition)
                                                        <option value="{{ $transition->value }}">{{ $transition->label() }}</option>
                                                    @endforeach
                                                </select>

                                                <button type="submit" class="btn btn--outline btn--sm" style="margin-top: 0.25rem">
                                                    {{ __('admin.common.save') }}
                                                </button>
                                            </form>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <p class="no-print">
        <a class="btn btn--ghost" href="{{ route('admin.workshops.index') }}">{{ __('admin.common.back') }}</a>
    </p>
@endsection
