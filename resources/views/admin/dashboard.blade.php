@extends('layouts.admin')

@section('title', __('admin.dashboard.title'))

@section('content')
    <div class="admin-page-header">
        <div>
            <h1>{{ __('admin.dashboard.title') }}</h1>
            <p>{{ __('admin.dashboard.welcome', ['name' => auth()->user()->name]) }}</p>
        </div>
    </div>

    {{-- ================== Compteurs ================== --}}
    <h2 class="visually-hidden">Chiffres du jour</h2>

    <div class="stat-grid">
        @foreach ([
            'new_appointments' => ['route' => 'admin.appointments.index', 'params' => ['statut' => 'new'], 'variant' => 'alert'],
            'appointments_to_call' => ['route' => 'admin.appointments.index', 'params' => ['statut' => 'to_call'], 'variant' => 'alert'],
            'overdue_callbacks' => ['route' => 'admin.appointments.index', 'params' => ['a_rappeler' => 1], 'variant' => 'alert'],
            'upcoming_workshops' => ['route' => 'admin.workshops.index', 'params' => [], 'variant' => 'success'],
            'recent_registrations' => ['route' => 'admin.registrations.index', 'params' => [], 'variant' => 'success'],
            'waiting_list' => ['route' => 'admin.registrations.index', 'params' => ['statut' => 'waiting_list'], 'variant' => ''],
            'unread_messages' => ['route' => 'admin.contact-requests.index', 'params' => ['statut' => 'new'], 'variant' => 'alert'],
            'partnership_requests' => ['route' => 'admin.partnership-requests.index', 'params' => [], 'variant' => ''],
        ] as $key => $config)
            @continue(! array_key_exists($key, $counters))

            @php
                $canSee = \Illuminate\Support\Facades\Route::has($config['route']);
            @endphp

            @if ($canSee)
                <a
                    class="stat {{ $config['variant'] ? 'stat--'.$config['variant'] : '' }}"
                    href="{{ route($config['route'], $config['params']) }}"
                >
                    <span class="stat__value">{{ $counters[$key] }}</span>
                    <span class="stat__label">{{ __("admin.dashboard.counters.{$key}") }}</span>
                </a>
            @endif
        @endforeach
    </div>

    {{-- ================== Documents à imprimer ================== --}}
    <section class="admin-panel" aria-labelledby="documents">
        <h2 class="admin-panel__title" id="documents">{{ __('admin.dashboard.documents_title') }}</h2>

        <p class="text-small text-muted">{{ __('admin.dashboard.documents_help') }}</p>

        <div class="btn-group" style="margin-top: 1rem">
            <a class="btn btn--outline" href="{{ route('leaflet') }}" target="_blank" rel="noopener">
                {{ __('admin.dashboard.leaflet') }}
                <span class="visually-hidden">({{ __('site.common.new_window') }})</span>
            </a>

            <a class="btn btn--outline" href="{{ route('partnership.brochure') }}" target="_blank" rel="noopener">
                {{ __('admin.dashboard.brochure') }}
                <span class="visually-hidden">({{ __('site.common.new_window') }})</span>
            </a>
        </div>
    </section>

    {{-- ================== Points de vigilance ================== --}}
    <section class="admin-panel" aria-labelledby="alertes">
        <h2 class="admin-panel__title" id="alertes">{{ __('admin.dashboard.alerts_title') }}</h2>

        @php $hasAlerts = $overdueGdpr > 0 || $guidesNeedingReview->isNotEmpty() || $overdueCallbacks->isNotEmpty(); @endphp

        @unless ($hasAlerts)
            <p class="text-muted">{{ __('admin.dashboard.no_alerts') }}</p>
        @endunless

        @if ($overdueGdpr > 0)
            <x-alert variant="danger">
                <p>
                    {{ $overdueGdpr }} {{ __('admin.dashboard.gdpr_overdue') }}.
                    <a href="{{ route('admin.gdpr.index') }}">{{ __('admin.nav.gdpr') }}</a>
                </p>
            </x-alert>
        @endif

        @if ($overdueCallbacks->isNotEmpty())
            <h3>{{ __('admin.dashboard.overdue_callbacks') }}</h3>

            <ul role="list" class="stack--sm stack">
                @foreach ($overdueCallbacks as $appointment)
                    <li>
                        <a href="{{ route('admin.appointments.show', $appointment) }}">
                            {{ $appointment->reference }} — {{ $appointment->fullName() }}
                        </a>
                        <span class="text-small text-muted">
                            ({{ $appointment->callback_on?->format('d/m/Y') }})
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($guidesNeedingReview->isNotEmpty())
            <h3 style="margin-top: 1rem">{{ __('admin.dashboard.guides_review') }}</h3>
            <p class="text-small text-muted">{{ __('admin.dashboard.guides_review_help') }}</p>

            <ul role="list" class="stack--sm stack">
                @foreach ($guidesNeedingReview as $guide)
                    <li><a href="{{ route('admin.guides.edit', $guide) }}">{{ $guide->title }}</a></li>
                @endforeach
            </ul>
        @endif
    </section>

    <div class="split">
        {{-- ================== Demandes à traiter ================== --}}
        <section class="admin-panel" aria-labelledby="demandes">
            <h2 class="admin-panel__title" id="demandes">{{ __('admin.dashboard.latest_appointments') }}</h2>

            @if ($latestAppointments->isEmpty())
                <p class="text-muted">{{ __('admin.common.empty') }}</p>
            @else
                <ul role="list" class="stack--sm stack">
                    @foreach ($latestAppointments as $appointment)
                        <li>
                            <a href="{{ route('admin.appointments.show', $appointment) }}">
                                {{ $appointment->reference }} — {{ $appointment->fullName() }}
                            </a>
                            <x-badge :variant="$appointment->status->badgeVariant()">
                                {{ $appointment->status->label() }}
                            </x-badge>
                            <span class="text-small text-muted">
                                {{ $appointment->municipality?->name ?? $appointment->municipality_name }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- ================== Places restantes ================== --}}
        <section class="admin-panel" aria-labelledby="places">
            <h2 class="admin-panel__title" id="places">{{ __('admin.dashboard.seats_title') }}</h2>

            @if ($upcomingWorkshops->isEmpty())
                <p class="text-muted">{{ __('admin.common.empty') }}</p>
            @else
                <ul role="list" class="stack--sm stack">
                    @foreach ($upcomingWorkshops as $workshop)
                        <li>
                            <a href="{{ route('admin.workshops.participants', $workshop) }}">{{ $workshop->title }}</a>
                            <span class="text-small text-muted">
                                {{ $workshop->date->format('d/m/Y') }} —
                                {{ __('admin.workshops.seats', [
                                    'occupied' => $workshop->occupiedSeats(),
                                    'capacity' => $workshop->capacity,
                                ]) }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    <div class="split">
        {{-- ================== Dernières inscriptions ================== --}}
        <section class="admin-panel" aria-labelledby="inscriptions">
            <h2 class="admin-panel__title" id="inscriptions">{{ __('admin.dashboard.latest_registrations') }}</h2>

            @if ($latestRegistrations->isEmpty())
                <p class="text-muted">{{ __('admin.common.empty') }}</p>
            @else
                <ul role="list" class="stack--sm stack">
                    @foreach ($latestRegistrations as $registration)
                        <li>
                            {{ $registration->reference }} — {{ $registration->fullName() }}
                            <x-badge :variant="$registration->status->badgeVariant()">
                                {{ $registration->status->label() }}
                            </x-badge>
                            <span class="text-small text-muted">{{ $registration->workshop?->title }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        {{-- ================== Messages non traités ================== --}}
        <section class="admin-panel" aria-labelledby="messages">
            <h2 class="admin-panel__title" id="messages">{{ __('admin.dashboard.unread_messages') }}</h2>

            @if ($unreadMessages->isEmpty())
                <p class="text-muted">{{ __('admin.common.empty') }}</p>
            @else
                <ul role="list" class="stack--sm stack">
                    @foreach ($unreadMessages as $message)
                        <li>
                            <a href="{{ route('admin.contact-requests.show', $message) }}">
                                {{ $message->reference }} — {{ $message->subject }}
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>

    {{-- ================== Statistiques ================== --}}
    <section class="admin-panel" aria-labelledby="statistiques">
        <h2 class="admin-panel__title" id="statistiques">{{ __('admin.dashboard.stats_title') }}</h2>

        <div class="stat-grid">
            <div class="stat stat--success">
                <span class="stat__value">{{ $fillRate }} %</span>
                <span class="stat__label">{{ __('admin.dashboard.fill_rate') }}</span>
            </div>

            <div class="stat">
                <span class="stat__value">{{ $cancellationRate }} %</span>
                <span class="stat__label">{{ __('admin.dashboard.cancellation_rate') }}</span>
            </div>
        </div>

        <h3>{{ __('admin.dashboard.monthly_title') }}</h3>

        {{-- Tableau plutôt qu'un graphique : lisible par un lecteur d'écran,
             imprimable, et sans dépendance JavaScript. --}}
        <div class="table-wrap">
            <table class="table">
                <caption>{{ __('admin.dashboard.monthly_title') }}</caption>
                <thead>
                    <tr>
                        <th scope="col">Mois</th>
                        <th scope="col">Demandes reçues</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($monthly as $period => $total)
                        <tr>
                            <th scope="row">
                                {{ \Carbon\CarbonImmutable::createFromFormat('Y-m', $period)->locale('fr')->isoFormat('MMMM YYYY') }}
                            </th>
                            <td>{{ $total }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>

    {{-- ================== Journal ================== --}}
    @if ($recentActivity->isNotEmpty())
        <section class="admin-panel" aria-labelledby="activite">
            <h2 class="admin-panel__title" id="activite">{{ __('admin.dashboard.activity_title') }}</h2>

            <ul class="timeline" role="list">
                @foreach ($recentActivity as $log)
                    <li>
                        {{ __("admin.audit.actions.{$log->action}", [], 'fr') }}
                        — {{ $log->subject_label ?? '—' }}
                        <span class="timeline__meta">
                            {{ $log->authorName() }}, {{ $log->created_at?->diffForHumans() }}
                        </span>
                    </li>
                @endforeach
            </ul>

            <p style="margin-top: 1rem">
                <a href="{{ route('admin.audit-logs.index') }}">{{ __('admin.nav.audit_logs') }}</a>
            </p>
        </section>
    @endif
@endsection
