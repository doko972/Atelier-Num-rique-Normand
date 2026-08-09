@extends('layouts.admin')

@section('title', __('admin.workshops.title'))

@section('content')
    <div class="admin-page-header">
        <h1>{{ __('admin.workshops.title') }}</h1>

        @can('create', \App\Models\Workshop::class)
            <a class="btn btn--primary" href="{{ route('admin.workshops.create') }}">
                {{ __('admin.common.create') }}
            </a>
        @endcan
    </div>

    <div class="admin-panel">
        <form method="GET" action="{{ route('admin.workshops.index') }}" class="filters" role="search">
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
                :options="\App\Enums\WorkshopStatus::options()"
                :value="$filters['statut'] ?? null"
                :empty-option="__('admin.common.all')"
            />

            <x-field
                name="periode"
                type="select"
                label="Période"
                :options="['a-venir' => __('admin.workshops.upcoming'), 'passes' => __('admin.workshops.past')]"
                :value="$filters['periode'] ?? 'a-venir'"
            />

            <div class="field">
                <button type="submit" class="btn btn--primary">{{ __('admin.common.filter') }}</button>
            </div>
        </form>

        @if ($workshops->isEmpty())
            <p class="table__empty">{{ __('admin.workshops.empty') }}</p>
        @else
            <div class="table-wrap">
                <table class="table table--stacked">
                    <caption class="visually-hidden">{{ __('admin.workshops.title') }}</caption>

                    <thead>
                        <tr>
                            <th scope="col">Date</th>
                            <th scope="col">Titre</th>
                            <th scope="col">Lieu</th>
                            <th scope="col">Places</th>
                            <th scope="col">Statut</th>
                            <th scope="col">{{ __('admin.common.actions') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($workshops as $workshop)
                            <tr>
                                <td data-label="Date">
                                    <time datetime="{{ $workshop->date->toDateString() }}">
                                        {{ $workshop->date->format('d/m/Y') }}
                                    </time><br>
                                    <span class="text-small text-muted">
                                        {{ $workshop->startsAt()->format('H\\hi') }}
                                    </span>
                                </td>

                                <td data-label="Titre">{{ $workshop->title }}</td>

                                <td data-label="Lieu">{{ $workshop->location?->name ?? '—' }}</td>

                                <td data-label="Places">
                                    {{ __('admin.workshops.seats', [
                                        'occupied' => $workshop->occupiedSeats(),
                                        'capacity' => $workshop->capacity,
                                    ]) }}

                                    @if (($workshop->waiting_count ?? 0) > 0)
                                        <br>
                                        <span class="text-small text-muted">
                                            {{ __('admin.workshops.waiting', ['count' => $workshop->waiting_count]) }}
                                        </span>
                                    @endif
                                </td>

                                <td data-label="Statut">
                                    <x-badge :variant="$workshop->status->badgeVariant()">
                                        {{ $workshop->status->label() }}
                                    </x-badge>
                                </td>

                                <td data-label="{{ __('admin.common.actions') }}">
                                    <div class="table__actions">
                                        <a class="btn btn--outline btn--sm" href="{{ route('admin.workshops.participants', $workshop) }}">
                                            {{ __('admin.workshops.participants') }}
                                            <span class="visually-hidden">: {{ $workshop->title }}</span>
                                        </a>

                                        @can('update', $workshop)
                                            <a class="btn btn--ghost btn--sm" href="{{ route('admin.workshops.edit', $workshop) }}">
                                                {{ __('admin.common.edit') }}
                                                <span class="visually-hidden">: {{ $workshop->title }}</span>
                                            </a>
                                        @endcan

                                        @can('delete', $workshop)
                                            <form
                                                method="POST"
                                                action="{{ route('admin.workshops.destroy', $workshop) }}"
                                                data-confirm="{{ __('admin.common.delete_confirm') }}"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn--danger btn--sm">
                                                    {{ __('admin.common.delete') }}
                                                    <span class="visually-hidden">: {{ $workshop->title }}</span>
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $workshops->links() }}
        @endif
    </div>
@endsection
