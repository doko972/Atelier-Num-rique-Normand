@extends('layouts.admin')

@section('title', __('admin.nav.contact_requests'))

@section('content')
    <div class="admin-page-header">
        <h1>{{ __('admin.nav.contact_requests') }}</h1>

        @can('export', \App\Models\ContactRequest::class)
            <a class="btn btn--outline" href="{{ route('admin.contact-requests.export', request()->query()) }}">
                {{ __('admin.common.export') }}
            </a>
        @endcan
    </div>

    <div class="admin-panel">
        <form method="GET" action="{{ route('admin.contact-requests.index') }}" class="filters" role="search">
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
                :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()"
                :value="$filters['statut'] ?? null"
                :empty-option="__('admin.common.all')"
            />

            <div class="field">
                <button type="submit" class="btn btn--primary">{{ __('admin.common.filter') }}</button>
            </div>
        </form>

        @if ($requests->isEmpty())
            <p class="table__empty">{{ __('admin.requests.empty') }}</p>
        @else
            <div class="table-wrap">
                <table class="table table--stacked">
                    <caption class="visually-hidden">{{ __('admin.nav.contact_requests') }}</caption>

                    <thead>
                        <tr>
                            <th scope="col">Référence</th>
                            <th scope="col">Reçu le</th>
                            <th scope="col">Personne</th>
                            <th scope="col">Sujet</th>
                            <th scope="col">Statut</th>
                            <th scope="col">{{ __('admin.common.actions') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($requests as $request)
                            <tr>
                                <td data-label="Référence">{{ $request->reference }}</td>
                                <td data-label="Reçu le">{{ $request->created_at?->format('d/m/Y') }}</td>

                                <td data-label="Personne">
                                    {{ $request->fullName() }}<br>
                                    <span class="text-small text-muted">{{ $request->phone ?: $request->email }}</span>
                                </td>

                                <td data-label="Sujet">{{ \Illuminate\Support\Str::limit($request->subject, 60) }}</td>

                                <td data-label="Statut">
                                    <x-badge :variant="$request->status->badgeVariant()">
                                        {{ $request->status->label() }}
                                    </x-badge>
                                </td>

                                <td data-label="{{ __('admin.common.actions') }}">
                                    <a class="btn btn--outline btn--sm" href="{{ route('admin.contact-requests.show', $request) }}">
                                        Ouvrir<span class="visually-hidden"> le message {{ $request->reference }}</span>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $requests->links() }}
        @endif
    </div>
@endsection
