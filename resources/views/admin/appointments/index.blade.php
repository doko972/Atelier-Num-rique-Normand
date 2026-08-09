@extends('layouts.admin')

@section('title', __('admin.appointments.title'))

@section('content')
    <div class="admin-page-header">
        <h1>{{ __('admin.appointments.title') }}</h1>

        @can('export', \App\Models\Appointment::class)
            <a class="btn btn--outline" href="{{ route('admin.appointments.export', request()->query()) }}">
                {{ __('admin.common.export') }}
            </a>
        @endcan
    </div>

    <div class="admin-panel">
        <form method="GET" action="{{ route('admin.appointments.index') }}" class="filters" role="search">
            <x-field
                name="recherche"
                type="search"
                :label="__('admin.common.search')"
                :value="$filters['recherche'] ?? null"
                :placeholder="__('admin.common.search_placeholder')"
            />

            <x-field
                name="statut"
                type="select"
                label="Statut"
                :options="\App\Enums\AppointmentStatus::options()"
                :value="$filters['statut'] ?? null"
                :empty-option="__('admin.common.all')"
            />

            <x-field
                name="type"
                type="select"
                label="Type"
                :options="\App\Enums\AppointmentType::options()"
                :value="$filters['type'] ?? null"
                :empty-option="__('admin.common.all')"
            />

            <x-field
                name="conseiller"
                type="select"
                label="Conseiller"
                :options="$advisers->pluck('name', 'id')->all()"
                :value="$filters['conseiller'] ?? null"
                :empty-option="__('admin.common.all')"
            />

            <div class="field">
                <button type="submit" class="btn btn--primary">{{ __('admin.common.filter') }}</button>
            </div>

            <div class="field">
                <a class="btn btn--ghost" href="{{ route('admin.appointments.index') }}">{{ __('admin.common.reset') }}</a>
            </div>
        </form>

        @if ($appointments->isEmpty())
            <p class="table__empty">{{ __('admin.appointments.empty') }}</p>
        @else
            <p class="text-small text-muted">{{ __('admin.common.results', ['count' => $appointments->total()]) }}</p>

            <div class="table-wrap" style="margin-top: 0.75rem">
                <table class="table table--stacked">
                    <caption class="visually-hidden">{{ __('admin.appointments.title') }}</caption>

                    <thead>
                        <tr>
                            <th scope="col">Référence</th>
                            <th scope="col">Reçue le</th>
                            <th scope="col">Personne</th>
                            <th scope="col">Commune</th>
                            <th scope="col">Type</th>
                            <th scope="col">Statut</th>
                            <th scope="col">Conseiller</th>
                            <th scope="col">{{ __('admin.common.actions') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($appointments as $appointment)
                            <tr>
                                <td data-label="Référence">{{ $appointment->reference }}</td>

                                <td data-label="Reçue le">
                                    <time datetime="{{ $appointment->created_at?->toDateString() }}">
                                        {{ $appointment->created_at?->format('d/m/Y') }}
                                    </time>
                                </td>

                                <td data-label="Personne">
                                    {{ $appointment->fullName() }}<br>
                                    <span class="text-small text-muted">{{ $appointment->phone }}</span>
                                </td>

                                <td data-label="Commune">
                                    {{ $appointment->municipality?->name ?? $appointment->municipality_name ?? '—' }}
                                </td>

                                <td data-label="Type">{{ $appointment->type->label() }}</td>

                                <td data-label="Statut">
                                    <x-badge :variant="$appointment->status->badgeVariant()">
                                        {{ $appointment->status->label() }}
                                    </x-badge>

                                    @if ($appointment->isCallbackOverdue())
                                        <x-badge variant="danger" square>{{ __('admin.appointments.overdue') }}</x-badge>
                                    @endif
                                </td>

                                <td data-label="Conseiller">{{ $appointment->assignee?->name ?? '—' }}</td>

                                <td data-label="{{ __('admin.common.actions') }}">
                                    <a class="btn btn--outline btn--sm" href="{{ route('admin.appointments.show', $appointment) }}">
                                        Ouvrir<span class="visually-hidden"> la demande {{ $appointment->reference }}</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $appointments->links() }}
        @endif
    </div>
@endsection
