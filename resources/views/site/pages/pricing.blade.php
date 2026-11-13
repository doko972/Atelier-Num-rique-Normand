@extends('layouts.site')

@section('title', __('site.pricing.title'))
@section('meta_description', __('site.pricing.intro'))

@section('breadcrumb')
    <x-breadcrumb :items="[__('site.pricing.title') => null]" />
@endsection

@section('content')
    <div class="page-header">
        <div class="container">
            <h1>{{ __('site.pricing.title') }}</h1>
            <p>{{ __('site.pricing.intro') }}</p>
        </div>
    </div>

    <section class="section">
        <div class="container">
            @if ($pricings->isEmpty())
                <x-alert variant="info">
                    <p>{{ __('site.pricing.empty') }}</p>
                </x-alert>
            @else
                <ul class="grid--wide grid" role="list">
                    @foreach ($pricings as $pricing)
                        <li class="price-card {{ $pricing->is_highlighted ? 'price-card--highlight' : '' }}">
                            <h2 class="card__title">{{ $pricing->label }}</h2>

                            <p class="price-card__amount">
                                {{ $pricing->formattedAmount() }}
                                @if ($pricing->unit)
                                    <span class="price-card__unit">{{ $pricing->unit }}</span>
                                @endif
                            </p>

                            @if ($sapNumber && $pricing->sap_eligible)
                                <p>
                                    <x-badge variant="success">{{ __('site.pricing.sap_badge') }}</x-badge>
                                    @if ($pricing->formattedAmountAfterTaxCredit())
                                        <br>
                                        <span class="text-small">
                                            {{ __('site.pricing.sap_after_credit', ['amount' => $pricing->formattedAmountAfterTaxCredit()]) }}
                                        </span>
                                    @endif
                                </p>
                            @endif

                            @if ($pricing->duration_minutes)
                                <p class="text-muted">
                                    {{ __('site.pricing.duration') }} :
                                    {{ __('site.resources.estimated_time', ['count' => $pricing->duration_minutes]) }}
                                </p>
                            @endif

                            @if ($pricing->description)
                                <p>{{ $pricing->description }}</p>
                            @endif

                            @if (filled($pricing->includes))
                                <h3 class="text-small" style="margin-top: 1rem">{{ __('site.pricing.includes') }}</h3>
                                <ul class="price-card__includes" role="list">
                                    @foreach ($pricing->includes as $item)
                                        <li>{{ $item }}</li>
                                    @endforeach
                                </ul>
                            @endif

                            <dl class="text-small text-muted" style="margin-top: 1rem">
                                @if ($pricing->travel_costs)
                                    <dt><strong>{{ __('site.pricing.travel') }}</strong></dt>
                                    <dd>{{ $pricing->travel_costs }}</dd>
                                @endif

                                @if ($pricing->payment_methods)
                                    <dt><strong>{{ __('site.pricing.payment') }}</strong></dt>
                                    <dd>{{ $pricing->payment_methods }}</dd>
                                @endif

                                @if ($pricing->cancellation_policy)
                                    <dt><strong>{{ __('site.pricing.cancellation') }}</strong></dt>
                                    <dd>{{ $pricing->cancellation_policy }}</dd>
                                @endif
                            </dl>

                            <p class="card__footer">
                                <a class="btn btn--outline btn--block" href="{{ route('appointments.create') }}">
                                    {{ $pricing->is_quote_only ? __('site.pricing.quote_cta') : __('site.cta.appointment') }}
                                </a>
                            </p>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    @if ($sapNumber)
        {{-- La distinction tient au lieu : à domicile, l'heure ouvre droit au
             crédit d'impôt ; à distance ou en salle, non. Le dire clairement
             évite qu'une personne compte sur un remboursement qui ne viendra
             pas. --}}
        <section class="section" aria-labelledby="credit-impot">
            <div class="container container--narrow">
                <h2 id="credit-impot">{{ __('site.pricing.sap_title') }}</h2>

                <p>{{ __('site.pricing.sap_intro', ['number' => $sapNumber]) }}</p>

                <div class="split">
                    <div>
                        <h3>{{ __('site.pricing.sap_eligible_title') }}</h3>
                        <ul>
                            @foreach (\App\Enums\InterventionActivity::sapCases() as $activity)
                                <li>{{ $activity->label() }}</li>
                            @endforeach
                        </ul>
                    </div>

                    <div>
                        <h3>{{ __('site.pricing.sap_not_eligible_title') }}</h3>
                        <ul>
                            @foreach (__('site.pricing.sap_not_eligible') as $item)
                                <li>{{ $item }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <p class="text-small text-muted">{{ __('site.pricing.sap_ceiling') }}</p>
            </div>
        </section>
    @endif

    <section class="section section--secondary">
        <div class="container container--narrow">
            <x-phone-cta />
        </div>
    </section>
@endsection
