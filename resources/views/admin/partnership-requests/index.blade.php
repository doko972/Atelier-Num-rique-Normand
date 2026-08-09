@extends('layouts.admin')

@section('title', __('admin.nav.partnership_requests'))

@section('content')
    <div class="admin-page-header">
        <h1>{{ __('admin.nav.partnership_requests') }}</h1>

        @can('export', \App\Models\PartnershipRequest::class)
            <a class="btn btn--outline" href="{{ route('admin.partnership-requests.export', request()->query()) }}">
                {{ __('admin.common.export') }}
            </a>
        @endcan
    </div>

    <div class="admin-panel">
        <form method="GET" action="{{ route('admin.partnership-requests.index') }}" class="filters" role="search">
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
                    <caption class="visually-hidden">{{ __('admin.nav.partnership_requests') }}</caption>

                    <thead>
                        <tr>
                            <th scope="col">Référence</th>
                            <th scope="col">Reçue le</th>
                            <th scope="col">Structure</th>
                            <th scope="col">Contact</th>
                            <th scope="col">Statut</th>
                            <th scope="col">{{ __('admin.common.actions') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($requests as $request)
                            <tr>
                                <td data-label="Référence">{{ $request->reference }}</td>
                                <td data-label="Reçue le">{{ $request->created_at?->format('d/m/Y') }}</td>

                                <td data-label="Structure">
                                    {{ $request->organisation_name }}<br>
                                    <span class="text-small text-muted">{{ $request->organisation_type->label() }}</span>
                                </td>

                                <td data-label="Contact">
                                    {{ $request->contact_name }}<br>
                                    <span class="text-small text-muted">{{ $request->email }}</span>
                                </td>

                                <td data-label="Statut">
                                    <x-badge :variant="$request->status->badgeVariant()">
                                        {{ $request->status->label() }}
                                    </x-badge>

                                    @if ($request->quote_requested)
                                        <x-badge variant="info" square>Devis demandé</x-badge>
                                    @endif
                                </td>

                                <td data-label="{{ __('admin.common.actions') }}">
                                    <a class="btn btn--outline btn--sm" href="{{ route('admin.partnership-requests.show', $request) }}">
                                        Ouvrir<span class="visually-hidden"> la demande {{ $request->reference }}</span>
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
