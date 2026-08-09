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

    <section class="section section--secondary">
        <div class="container container--narrow">
            <x-phone-cta />
        </div>
    </section>
@endsection
