@extends('layouts.admin')

@section('title', __('admin.appointments.detail', ['reference' => $appointment->reference]))

@section('content')
    <div class="admin-page-header">
        <div>
            <h1>{{ __('admin.appointments.detail', ['reference' => $appointment->reference]) }}</h1>
            <p>
                <x-badge :variant="$appointment->status->badgeVariant()">{{ $appointment->status->label() }}</x-badge>
                {{ $appointment->type->label() }} —
                reçue le {{ $appointment->created_at?->format('d/m/Y à H\\hi') }}
            </p>
        </div>

        <a class="btn btn--ghost" href="{{ route('admin.appointments.index') }}">{{ __('admin.common.back') }}</a>
    </div>

    @if ($appointment->isAnonymised())
        <x-alert variant="warning">
            <p>Cette demande a été rendue anonyme à la suite d’une demande d’effacement.</p>
        </x-alert>
    @endif

    <div class="split--aside split">
        <div>
            {{-- ============ La personne ============ --}}
            <section class="admin-panel" aria-labelledby="personne">
                <h2 class="admin-panel__title" id="personne">{{ __('admin.appointments.requester') }}</h2>

                <dl class="stack--sm stack">
                    <div>
                        <dt><strong>Nom</strong></dt>
                        <dd>{{ $appointment->fullName() }}</dd>
                    </div>

                    <div>
                        <dt><strong>Téléphone</strong></dt>
                        <dd>
                            @if ($appointment->phone)
                                <a href="tel:{{ preg_replace('/\s+/', '', $appointment->phone) }}">{{ $appointment->phone }}</a>
                            @else
                                —
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt><strong>Adresse électronique</strong></dt>
                        <dd>
                            @if ($appointment->email)
                                <a href="mailto:{{ $appointment->email }}">{{ $appointment->email }}</a>
                            @else
                                <span class="text-muted">{{ __('admin.appointments.no_email') }}</span>
                            @endif
                        </dd>
                    </div>

                    <div>
                        <dt><strong>Commune</strong></dt>
                        <dd>{{ $appointment->municipality?->name ?? $appointment->municipality_name ?? '—' }}</dd>
                    </div>

                    <div>
                        <dt><strong>Préférence de contact</strong></dt>
                        <dd>
                            {{ $appointment->contact_preference->label() }}
                            @if ($appointment->voice_message_allowed)
                                — message vocal autorisé
                            @endif
                        </dd>
                    </div>

                    @if ($appointment->has_mobility_difficulty)
                        <div>
                            <dt><strong>Mobilité</strong></dt>
                            <dd>Difficultés à se déplacer signalées.</dd>
                        </div>
                    @endif
                </dl>
            </section>

            {{-- ============ Le besoin ============ --}}
            <section class="admin-panel" aria-labelledby="besoin">
                <h2 class="admin-panel__title" id="besoin">{{ __('admin.appointments.need') }}</h2>

                <p>{!! nl2br(e($appointment->need_description)) !!}</p>

                <dl class="stack--sm stack" style="margin-top: 1rem">
                    @if ($appointment->device)
                        <div>
                            <dt><strong>Appareil</strong></dt>
                            <dd>{{ $appointment->device->label() }}</dd>
                        </div>
                    @endif

                    @if ($appointment->availability)
                        <div>
                            <dt><strong>Disponibilités</strong></dt>
                            <dd>{!! nl2br(e($appointment->availability)) !!}</dd>
                        </div>
                    @endif

                    <div>
                        <dt><strong>Déplacement à domicile</strong></dt>
                        <dd>{{ $appointment->home_visit_requested ? __('admin.common.yes') : __('admin.common.no') }}</dd>
                    </div>
                </dl>
            </section>

            {{-- ============ Notes ============ --}}
            <section class="admin-panel" aria-labelledby="notes">
                <h2 class="admin-panel__title" id="notes">{{ __('admin.appointments.notes') }}</h2>

                <form method="POST" action="{{ route('admin.appointments.note', $appointment) }}">
                    @csrf

                    <x-field
                        name="body"
                        type="textarea"
                        :label="__('admin.appointments.add_note')"
                        :rows="3"
                        maxlength="2000"
                        :placeholder="__('admin.appointments.note_placeholder')"
                        required
                    />

                    <button type="submit" class="btn btn--primary">{{ __('admin.common.save') }}</button>
                </form>

                @if ($appointment->notes->isNotEmpty())
                    <ul class="timeline" role="list" style="margin-top: 1.5rem">
                        @foreach ($appointment->notes as $note)
                            <li>
                                {!! nl2br(e($note->body)) !!}
                                <span class="timeline__meta">
                                    {{ $note->authorName() }} — {{ $note->created_at?->format('d/m/Y à H\\hi') }}
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <aside>
            {{-- ============ Suivi ============ --}}
            <section class="admin-panel" aria-labelledby="suivi">
                <h2 class="admin-panel__title" id="suivi">{{ __('admin.appointments.follow_up') }}</h2>

                <form method="POST" action="{{ route('admin.appointments.update', $appointment) }}">
                    @csrf
                    @method('PUT')

                    <x-field
                        name="status"
                        type="select"
                        label="Statut"
                        :options="collect($transitions)->mapWithKeys(fn ($status) => [$status->value => $status->label()])
                            ->prepend($appointment->status->label(), $appointment->status->value)->all()"
                        :value="$appointment->status->value"
                        required
                    />

                    <x-field
                        name="callback_on"
                        type="date"
                        :label="__('admin.appointments.callback_on')"
                        :value="$appointment->callback_on?->format('Y-m-d')"
                    />

                    <x-field
                        name="scheduled_for"
                        type="datetime-local"
                        :label="__('admin.appointments.scheduled_for')"
                        :value="$appointment->scheduled_for?->format('Y-m-d\TH:i')"
                    />

                    <x-field
                        name="location_id"
                        type="select"
                        :label="__('admin.appointments.location')"
                        :options="$locations->pluck('name', 'id')->all()"
                        :value="$appointment->location_id"
                        :empty-option="__('admin.common.none')"
                    />

                    @if ($appointment->canReceiveEmail())
                        <x-checkbox
                            name="notify"
                            :label="__('admin.appointments.notify')"
                            :description="__('admin.appointments.notify_help')"
                        />
                    @endif

                    <button type="submit" class="btn btn--primary btn--block">{{ __('admin.common.save') }}</button>
                </form>
            </section>

            {{-- ============ Affectation ============ --}}
            <section class="admin-panel" aria-labelledby="affectation">
                <h2 class="admin-panel__title" id="affectation">{{ __('admin.appointments.assignee') }}</h2>

                <form method="POST" action="{{ route('admin.appointments.assign', $appointment) }}">
                    @csrf
                    @method('PUT')

                    <x-field
                        name="assigned_to"
                        type="select"
                        :label="__('admin.appointments.assignee')"
                        :options="$advisers->pluck('name', 'id')->all()"
                        :value="$appointment->assigned_to"
                        :empty-option="__('admin.common.none')"
                    />

                    <button type="submit" class="btn btn--outline btn--block">{{ __('admin.common.save') }}</button>
                </form>
            </section>

            {{-- ============ Consentements ============ --}}
            @if ($appointment->consentLogs->isNotEmpty())
                <section class="admin-panel" aria-labelledby="consentements">
                    <h2 class="admin-panel__title" id="consentements">{{ __('admin.appointments.consents') }}</h2>

                    <ul role="list" class="stack--sm stack text-small">
                        @foreach ($appointment->consentLogs as $consent)
                            <li>
                                <strong>{{ $consent->purpose->label() }}</strong><br>
                                <span class="text-muted">
                                    {{ $consent->granted_at?->format('d/m/Y à H\\hi') }} — version {{ $consent->version }}
                                </span><br>
                                <span class="text-muted">« {{ $consent->statement }} »</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            {{-- ============ RGPD ============ --}}
            @can('anonymise', $appointment)
                <section class="admin-panel" aria-labelledby="rgpd">
                    <h2 class="admin-panel__title" id="rgpd">Données personnelles</h2>

                    <p class="text-small text-muted">{{ __('rgpd.anonymisation_notice') }}</p>

                    <form
                        method="POST"
                        action="{{ route('admin.appointments.anonymise', $appointment) }}"
                        data-confirm="{{ __('admin.appointments.anonymise_confirm') }}"
                    >
                        @csrf
                        <button type="submit" class="btn btn--danger btn--block">
                            {{ __('rgpd.execute') }}
                        </button>
                    </form>
                </section>
            @endcan
        </aside>
    </div>
@endsection
