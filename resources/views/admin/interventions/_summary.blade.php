{{--
    Synthèse SAP / hors SAP d'une année.

    Reprise sur la liste des prestations, le bilan et le tableau de bord : la
    même lecture partout, pour ne jamais avoir deux chiffres contradictoires.

    @var \App\Support\SapYearSummary $summary
--}}
@php
    use App\Support\FrenchFormat;

    $status = $summary->status();
    $previous = $summary->year - 1;
@endphp

<section class="admin-panel" aria-labelledby="synthese-sap-{{ $summary->year }}">
    <h2 class="admin-panel__title" id="synthese-sap-{{ $summary->year }}">
        {{ __('admin.interventions.summary.title', ['year' => $summary->year]) }}
        <x-badge :variant="$summary->statusVariant()">{{ __("admin.interventions.summary.status.{$status}") }}</x-badge>
    </h2>

    @if ($status === 'empty')
        <p class="text-muted">{{ __('admin.interventions.summary.empty', ['year' => $summary->year]) }}</p>
    @else
        <div class="stat-grid" style="margin-top: 1rem">
            <div class="stat {{ $summary->needsAttention() ? 'stat--alert' : 'stat--success' }}">
                <span class="stat__value">{{ number_format((float) $summary->sapShare(), 1, ',', ' ') }} %</span>
                <span class="stat__label">
                    {{ __('admin.interventions.summary.sap_share') }}
                    — {{ __('admin.interventions.summary.sap_share_target') }}
                </span>
            </div>

            <div class="stat">
                <span class="stat__value">{{ FrenchFormat::money($summary->sapCents) }}</span>
                <span class="stat__label">
                    {{ __('admin.interventions.summary.sap_amount') }}
                    ({{ FrenchFormat::duration($summary->sapMinutes) }})
                </span>
            </div>

            <div class="stat">
                <span class="stat__value">{{ FrenchFormat::money($summary->otherCents) }}</span>
                <span class="stat__label">
                    {{ __('admin.interventions.summary.other_amount') }}
                    ({{ FrenchFormat::duration($summary->otherMinutes) }})
                </span>
            </div>

            @if (! $summary->isFirstYear())
                <div class="stat {{ $summary->legalRemainingCents() < 0 ? 'stat--alert' : '' }}">
                    <span class="stat__value">{{ FrenchFormat::money($summary->legalCapCents()) }}</span>
                    <span class="stat__label">
                        {{ __('admin.interventions.summary.legal_cap', ['year' => $summary->year]) }} —
                        {{ __('admin.interventions.summary.legal_cap_help', [
                            'previous' => $previous,
                            'total' => FrenchFormat::money($summary->previousYearTotalCents),
                        ]) }}
                    </span>
                </div>
            @endif
        </div>

        @if (! $summary->isFirstYear())
            <p>
                @if ($summary->legalRemainingCents() >= 0)
                    {{ __('admin.interventions.summary.remaining', ['amount' => FrenchFormat::money($summary->legalRemainingCents())]) }}
                @else
                    <strong>{{ __('admin.interventions.summary.exceeded', ['amount' => FrenchFormat::money(-$summary->legalRemainingCents())]) }}</strong>
                @endif
            </p>
        @endif
    @endif

    @if ($status === 'over')
        <x-alert variant="danger">
            <p>{{ __('admin.interventions.summary.over_help') }}</p>
        </x-alert>
    @elseif ($status === 'warning')
        <x-alert variant="warning">
            <p>{{ __('admin.interventions.summary.warning_help') }}</p>
        </x-alert>
    @endif

    @if ($status !== 'empty' && $summary->isFirstYear())
        <p class="text-small text-muted">{{ __('admin.interventions.summary.first_year', ['previous' => $previous]) }}</p>
    @endif
</section>
