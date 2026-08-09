@extends('layouts.admin')

@section('title', 'Message '.$contactRequest->reference)

@section('content')
    <div class="admin-page-header">
        <div>
            <h1>Message {{ $contactRequest->reference }}</h1>
            <p>
                <x-badge :variant="$contactRequest->status->badgeVariant()">
                    {{ $contactRequest->status->label() }}
                </x-badge>
                reçu le {{ $contactRequest->created_at?->format('d/m/Y à H\\hi') }}
            </p>
        </div>

        <a class="btn btn--ghost" href="{{ route('admin.contact-requests.index') }}">{{ __('admin.common.back') }}</a>
    </div>

    <div class="split--aside split">
        <div>
            <section class="admin-panel" aria-labelledby="message">
                <h2 class="admin-panel__title" id="message">{{ __('admin.requests.message_title') }}</h2>

                <p><strong>{{ $contactRequest->subject }}</strong></p>
                <p>{!! nl2br(e($contactRequest->message)) !!}</p>
            </section>

            <section class="admin-panel" aria-labelledby="expediteur">
                <h2 class="admin-panel__title" id="expediteur">Expéditeur</h2>

                <dl class="stack--sm stack">
                    <div>
                        <dt><strong>Nom</strong></dt>
                        <dd>{{ $contactRequest->fullName() }}</dd>
                    </div>

                    <div>
                        <dt><strong>Téléphone</strong></dt>
                        <dd>
                            @if ($contactRequest->phone)
                                <a href="tel:{{ preg_replace('/\s+/', '', $contactRequest->phone) }}">{{ $contactRequest->phone }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt><strong>Adresse électronique</strong></dt>
                        <dd>
                            @if ($contactRequest->email)
                                <a href="mailto:{{ $contactRequest->email }}?subject={{ rawurlencode('Votre message '.$contactRequest->reference) }}">
                                    {{ $contactRequest->email }}
                                </a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt><strong>Commune</strong></dt>
                        <dd>{{ $contactRequest->municipality?->name ?? '—' }}</dd>
                    </div>

                    <div>
                        <dt><strong>Préférence de contact</strong></dt>
                        <dd>
                            {{ $contactRequest->contact_preference->label() }}
                            @if ($contactRequest->voice_message_allowed)
                                — message vocal autorisé
                            @endif
                        </dd>
                    </div>
                </dl>
            </section>
        </div>

        <aside>
            <section class="admin-panel" aria-labelledby="suivi">
                <h2 class="admin-panel__title" id="suivi">{{ __('admin.requests.follow_up') }}</h2>

                <form method="POST" action="{{ route('admin.contact-requests.update', $contactRequest) }}">
                    @csrf
                    @method('PUT')

                    <x-field
                        name="status"
                        type="select"
                        label="Statut"
                        :options="collect($statuses)->mapWithKeys(fn ($status) => [$status->value => $status->label()])->all()"
                        :value="$contactRequest->status->value"
                        required
                    />

                    <x-field
                        name="assigned_to"
                        type="select"
                        label="Conseiller"
                        :options="$advisers->pluck('name', 'id')->all()"
                        :value="$contactRequest->assigned_to"
                        :empty-option="__('admin.common.none')"
                    />

                    <x-field
                        name="internal_notes"
                        type="textarea"
                        label="Notes internes"
                        :value="$contactRequest->internal_notes"
                        :rows="4"
                    />

                    <button type="submit" class="btn btn--primary btn--block">{{ __('admin.common.save') }}</button>
                </form>
            </section>

            @can('anonymise', $contactRequest)
                <section class="admin-panel" aria-labelledby="rgpd">
                    <h2 class="admin-panel__title" id="rgpd">Données personnelles</h2>
                    <p class="text-small text-muted">{{ __('rgpd.anonymisation_notice') }}</p>

                    <form
                        method="POST"
                        action="{{ route('admin.contact-requests.anonymise', $contactRequest) }}"
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
