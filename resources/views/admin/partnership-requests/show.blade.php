@extends('layouts.admin')

@section('title', 'Demande de partenariat '.$partnershipRequest->reference)

@section('content')
    <div class="admin-page-header">
        <div>
            <h1>Demande {{ $partnershipRequest->reference }}</h1>
            <p>
                <x-badge :variant="$partnershipRequest->status->badgeVariant()">
                    {{ $partnershipRequest->status->label() }}
                </x-badge>
                reçue le {{ $partnershipRequest->created_at?->format('d/m/Y à H\\hi') }}
            </p>
        </div>

        <a class="btn btn--ghost" href="{{ route('admin.partnership-requests.index') }}">{{ __('admin.common.back') }}</a>
    </div>

    <div class="split--aside split">
        <div>
            <section class="admin-panel" aria-labelledby="structure">
                <h2 class="admin-panel__title" id="structure">{{ __('admin.requests.organisation_title') }}</h2>

                <dl class="stack--sm stack">
                    <div>
                        <dt><strong>Structure</strong></dt>
                        <dd>{{ $partnershipRequest->organisation_name }} ({{ $partnershipRequest->organisation_type->label() }})</dd>
                    </div>

                    <div>
                        <dt><strong>Contact</strong></dt>
                        <dd>
                            {{ $partnershipRequest->contact_name }}
                            @if ($partnershipRequest->contact_role)
                                — {{ $partnershipRequest->contact_role }}
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt><strong>Adresse électronique</strong></dt>
                        <dd><a href="mailto:{{ $partnershipRequest->email }}">{{ $partnershipRequest->email }}</a></dd>
                    </div>

                    <div>
                        <dt><strong>Téléphone</strong></dt>
                        <dd>{{ $partnershipRequest->phone ?: '—' }}</dd>
                    </div>

                    <div>
                        <dt><strong>Commune</strong></dt>
                        <dd>{{ $partnershipRequest->municipality?->name ?? $partnershipRequest->municipality_name ?? '—' }}</dd>
                    </div>
                </dl>
            </section>

            <section class="admin-panel" aria-labelledby="besoins">
                <h2 class="admin-panel__title" id="besoins">{{ __('admin.requests.needs_title') }}</h2>

                @if (filled($partnershipRequest->needs))
                    <ul class="check-list check-list--yes" role="list">
                        @foreach ($partnershipRequest->needs as $need)
                            <li>{{ $needLabels[$need] ?? $need }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-muted">—</p>
                @endif

                <dl class="stack--sm stack" style="margin-top: 1rem">
                    <div>
                        <dt><strong>Public concerné</strong></dt>
                        <dd>{{ $partnershipRequest->audience ?: '—' }}</dd>
                    </div>

                    <div>
                        <dt><strong>Participants estimés</strong></dt>
                        <dd>{{ $partnershipRequest->estimated_participants ?: '—' }}</dd>
                    </div>

                    <div>
                        <dt><strong>Période souhaitée</strong></dt>
                        <dd>{{ $partnershipRequest->desired_period ?: '—' }}</dd>
                    </div>

                    <div>
                        <dt><strong>Devis demandé</strong></dt>
                        <dd>{{ $partnershipRequest->quote_requested ? __('admin.common.yes') : __('admin.common.no') }}</dd>
                    </div>
                </dl>

                @if ($partnershipRequest->message)
                    <h3 style="margin-top: 1.5rem">Message</h3>
                    <p>{!! nl2br(e($partnershipRequest->message)) !!}</p>
                @endif
            </section>
        </div>

        <aside>
            <section class="admin-panel" aria-labelledby="suivi">
                <h2 class="admin-panel__title" id="suivi">{{ __('admin.requests.follow_up') }}</h2>

                <form method="POST" action="{{ route('admin.partnership-requests.update', $partnershipRequest) }}">
                    @csrf
                    @method('PUT')

                    <x-field
                        name="status"
                        type="select"
                        label="Statut"
                        :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()"
                        :value="$partnershipRequest->status->value"
                        required
                    />

                    <x-field
                        name="assigned_to"
                        type="select"
                        label="Conseiller"
                        :options="$advisers->pluck('name', 'id')->all()"
                        :value="$partnershipRequest->assigned_to"
                        :empty-option="__('admin.common.none')"
                    />

                    <x-field
                        name="internal_notes"
                        type="textarea"
                        label="Notes internes"
                        :value="$partnershipRequest->internal_notes"
                        :rows="4"
                    />

                    <button type="submit" class="btn btn--primary btn--block">{{ __('admin.common.save') }}</button>
                </form>
            </section>

            @can('anonymise', $partnershipRequest)
                <section class="admin-panel" aria-labelledby="rgpd">
                    <h2 class="admin-panel__title" id="rgpd">Données personnelles</h2>
                    <p class="text-small text-muted">{{ __('rgpd.anonymisation_notice') }}</p>

                    <form
                        method="POST"
                        action="{{ route('admin.partnership-requests.anonymise', $partnershipRequest) }}"
                        data-confirm="{{ __('rgpd.execute_confirm') }}"
                    >
                        @csrf
                        <button type="submit" class="btn btn--danger btn--block">{{ __('rgpd.execute') }}</button>
                    </form>
                </section>
            @endcan
        </aside>
    </div>
@endsection
