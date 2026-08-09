@extends('layouts.admin')

@section('title', __('rgpd.title'))

@section('content')
    <div class="admin-page-header">
        <div>
            <h1>{{ __('rgpd.title') }}</h1>
            <p>{{ __('rgpd.intro') }}</p>
        </div>
    </div>

    <x-alert variant="warning">
        <p>{{ __('rgpd.identity_check_required') }}</p>
        <p class="text-small">{{ __('rgpd.identity_required_help') }}</p>
    </x-alert>

    {{-- ================= Demandes d'accès ================= --}}
    <section class="admin-panel" aria-labelledby="acces">
        <h2 class="admin-panel__title" id="acces">{{ __('rgpd.exports_title') }}</h2>

        @if ($exportRequests->isEmpty())
            <p class="table__empty">{{ __('admin.common.empty') }}</p>
        @else
            <div class="table-wrap">
                <table class="table table--stacked">
                    <caption class="visually-hidden">{{ __('rgpd.exports_title') }}</caption>

                    <thead>
                        <tr>
                            <th scope="col">Référence</th>
                            <th scope="col">Demandeur</th>
                            <th scope="col">Type</th>
                            <th scope="col">Statut</th>
                            <th scope="col">Échéance</th>
                            <th scope="col">{{ __('admin.common.actions') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($exportRequests as $request)
                            <tr>
                                <td data-label="Référence">{{ $request->reference }}</td>

                                <td data-label="Demandeur">
                                    {{ $request->requester_name }}<br>
                                    <span class="text-small text-muted">
                                        {{ $request->requester_email ?: $request->requester_phone }}
                                    </span>
                                </td>

                                <td data-label="Type">{{ $request->type->label() }}</td>

                                <td data-label="Statut">
                                    <x-badge :variant="$request->status->badgeVariant()">
                                        {{ $request->status->label() }}
                                    </x-badge>

                                    @if ($request->identity_verified)
                                        <x-badge variant="success" square>Identité vérifiée</x-badge>
                                    @endif
                                </td>

                                <td data-label="Échéance">
                                    {{ $request->due_on?->format('d/m/Y') }}
                                    @if ($request->isOverdue())
                                        <br><x-badge variant="danger">{{ __('rgpd.overdue') }}</x-badge>
                                    @endif
                                </td>

                                <td data-label="{{ __('admin.common.actions') }}">
                                    <div class="table__actions">
                                        @unless ($request->identity_verified)
                                            <form method="POST" action="{{ route('admin.gdpr.identity', ['type' => 'export', 'id' => $request->id]) }}">
                                                @csrf
                                                <button type="submit" class="btn btn--outline btn--sm">Identité vérifiée</button>
                                            </form>
                                        @else
                                            <a class="btn btn--outline btn--sm" href="{{ route('admin.gdpr.exports.preview', $request) }}">
                                                {{ __('rgpd.preview') }}
                                            </a>
                                        @endunless

                                        <form method="POST" action="{{ route('admin.gdpr.status', ['type' => 'export', 'id' => $request->id]) }}">
                                            @csrf
                                            @method('PUT')

                                            <label class="visually-hidden" for="statut-export-{{ $request->id }}">
                                                Statut de la demande {{ $request->reference }}
                                            </label>

                                            <select class="field__control" id="statut-export-{{ $request->id }}" name="status">
                                                @foreach ($statuses as $value => $label)
                                                    <option value="{{ $value }}" @selected($request->status->value === $value)>
                                                        {{ $label }}
                                                    </option>
                                                @endforeach
                                            </select>

                                            <button type="submit" class="btn btn--ghost btn--sm" style="margin-top: 0.25rem">
                                                {{ __('admin.common.save') }}
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $exportRequests->links() }}
        @endif

        <details style="margin-top: 1.5rem">
            <summary class="btn btn--primary">{{ __('rgpd.new_export') }}</summary>

            <form method="POST" action="{{ route('admin.gdpr.exports.store') }}" class="form" style="margin-top: 1rem">
                @csrf

                <x-field name="type" type="select" :label="__('rgpd.attributes.type')" :options="$types" required />
                <x-field name="requester_name" :label="__('rgpd.attributes.requester_name')" required />
                <x-field name="requester_email" type="email" :label="__('rgpd.attributes.requester_email')" />
                <x-field name="requester_phone" type="tel" :label="__('rgpd.attributes.requester_phone')" />
                <x-field name="details" type="textarea" :label="__('rgpd.attributes.details')" :rows="3" />

                <button type="submit" class="btn btn--primary">{{ __('admin.common.save') }}</button>
            </form>
        </details>
    </section>

    {{-- ================= Demandes d'effacement ================= --}}
    <section class="admin-panel" aria-labelledby="effacement">
        <h2 class="admin-panel__title" id="effacement">{{ __('rgpd.deletions_title') }}</h2>

        <p class="text-small text-muted">{{ __('rgpd.anonymisation_notice') }}</p>

        @if ($deletionRequests->isEmpty())
            <p class="table__empty">{{ __('admin.common.empty') }}</p>
        @else
            <div class="table-wrap">
                <table class="table table--stacked">
                    <caption class="visually-hidden">{{ __('rgpd.deletions_title') }}</caption>

                    <thead>
                        <tr>
                            <th scope="col">Référence</th>
                            <th scope="col">Demandeur</th>
                            <th scope="col">Périmètre</th>
                            <th scope="col">Statut</th>
                            <th scope="col">Échéance</th>
                            <th scope="col">{{ __('admin.common.actions') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($deletionRequests as $request)
                            <tr>
                                <td data-label="Référence">{{ $request->reference }}</td>

                                <td data-label="Demandeur">
                                    {{ $request->requester_name }}<br>
                                    <span class="text-small text-muted">
                                        {{ $request->requester_email ?: $request->requester_phone }}
                                    </span>
                                </td>

                                <td data-label="Périmètre">{{ $scopes[$request->scope] ?? $request->scope }}</td>

                                <td data-label="Statut">
                                    <x-badge :variant="$request->status->badgeVariant()">
                                        {{ $request->status->label() }}
                                    </x-badge>

                                    @if ($request->records_anonymised > 0)
                                        <br>
                                        <span class="text-small text-muted">
                                            {{ $request->records_anonymised }} enregistrement(s) traité(s)
                                        </span>
                                    @endif
                                </td>

                                <td data-label="Échéance">
                                    {{ $request->due_on?->format('d/m/Y') }}
                                    @if ($request->isOverdue())
                                        <br><x-badge variant="danger">{{ __('rgpd.overdue') }}</x-badge>
                                    @endif
                                </td>

                                <td data-label="{{ __('admin.common.actions') }}">
                                    <div class="table__actions">
                                        @unless ($request->identity_verified)
                                            <form method="POST" action="{{ route('admin.gdpr.identity', ['type' => 'suppression', 'id' => $request->id]) }}">
                                                @csrf
                                                <button type="submit" class="btn btn--outline btn--sm">Identité vérifiée</button>
                                            </form>
                                        @elseif ($request->status->isOpen())
                                            <form
                                                method="POST"
                                                action="{{ route('admin.gdpr.deletions.execute', $request) }}"
                                                data-confirm="{{ __('rgpd.execute_confirm') }}"
                                            >
                                                @csrf
                                                <button type="submit" class="btn btn--danger btn--sm">{{ __('rgpd.execute') }}</button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $deletionRequests->links() }}
        @endif

        <details style="margin-top: 1.5rem">
            <summary class="btn btn--primary">{{ __('rgpd.new_deletion') }}</summary>

            <form method="POST" action="{{ route('admin.gdpr.deletions.store') }}" class="form" style="margin-top: 1rem">
                @csrf

                <x-field name="requester_name" :label="__('rgpd.attributes.requester_name')" required id="champ-suppression-nom" />
                <x-field name="requester_email" type="email" :label="__('rgpd.attributes.requester_email')" id="champ-suppression-email" />
                <x-field name="requester_phone" type="tel" :label="__('rgpd.attributes.requester_phone')" id="champ-suppression-telephone" />
                <x-field name="scope" type="select" :label="__('rgpd.attributes.scope')" :options="$scopes" required />
                <x-field name="details" type="textarea" :label="__('rgpd.attributes.details')" :rows="3" id="champ-suppression-details" />

                <button type="submit" class="btn btn--primary">{{ __('admin.common.save') }}</button>
            </form>
        </details>
    </section>
@endsection
