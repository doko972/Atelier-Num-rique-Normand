@extends('layouts.admin')

@section('title', __('admin.registrations.title'))

@section('content')
    <div class="admin-page-header">
        <h1>{{ __('admin.registrations.title') }}</h1>

        @can('export', \App\Models\WorkshopRegistration::class)
            <a class="btn btn--outline" href="{{ route('admin.registrations.export', request()->query()) }}">
                {{ __('admin.common.export') }}
            </a>
        @endcan
    </div>

    <div class="admin-panel">
        <form method="GET" action="{{ route('admin.registrations.index') }}" class="filters" role="search">
            <x-field
                name="recherche"
                type="search"
                :label="__('admin.common.search')"
                :value="$filters['recherche'] ?? null"
            />

            <x-field
                name="statut"
                type="select"
                label="Statut"
                :options="\App\Enums\RegistrationStatus::options()"
                :value="$filters['statut'] ?? null"
                :empty-option="__('admin.common.all')"
            />

            <x-field
                name="atelier"
                type="select"
                label="Atelier"
                :options="$workshops->mapWithKeys(fn ($workshop) => [
                    $workshop->id => $workshop->date->format('d/m/Y').' — '.$workshop->title,
                ])->all()"
                :value="$filters['atelier'] ?? null"
                :empty-option="__('admin.common.all')"
            />

            <div class="field">
                <button type="submit" class="btn btn--primary">{{ __('admin.common.filter') }}</button>
            </div>
        </form>

        @if ($registrations->isEmpty())
            <p class="table__empty">{{ __('admin.registrations.empty') }}</p>
        @else
            <div class="table-wrap">
                <table class="table table--stacked">
                    <caption class="visually-hidden">{{ __('admin.registrations.title') }}</caption>

                    <thead>
                        <tr>
                            <th scope="col">Référence</th>
                            <th scope="col">Personne</th>
                            <th scope="col">Atelier</th>
                            <th scope="col">Commune</th>
                            <th scope="col">Statut</th>
                            <th scope="col">Inscrite le</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($registrations as $registration)
                            <tr>
                                <td data-label="Référence">{{ $registration->reference }}</td>

                                <td data-label="Personne">
                                    {{ $registration->fullName() }}<br>
                                    <span class="text-small text-muted">{{ $registration->phone }}</span>
                                </td>

                                <td data-label="Atelier">
                                    @if ($registration->workshop)
                                        <a href="{{ route('admin.workshops.participants', $registration->workshop) }}">
                                            {{ $registration->workshop->title }}
                                        </a><br>
                                        <span class="text-small text-muted">
                                            {{ $registration->workshop->date->format('d/m/Y') }}
                                        </span>
                                    @else
                                        —
                                    @endif
                                </td>

                                <td data-label="Commune">
                                    {{ $registration->municipality?->name ?? $registration->municipality_name ?? '—' }}
                                </td>

                                <td data-label="Statut">
                                    <x-badge :variant="$registration->status->badgeVariant()">
                                        {{ $registration->status->label() }}
                                    </x-badge>
                                </td>

                                <td data-label="Inscrite le">{{ $registration->created_at?->format('d/m/Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $registrations->links() }}
        @endif
    </div>
@endsection
