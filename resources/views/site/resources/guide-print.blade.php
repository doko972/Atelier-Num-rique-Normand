@extends('layouts.print')

@section('title', $guide->title)

@section('content')
    <h1>{{ $guide->title }}</h1>

    <p class="text-lead">{{ $guide->summary }}</p>

    <ul class="guide-meta" role="list">
        <li><strong>{{ __('site.resources.level') }} :</strong> {{ $guide->level->label() }}</li>

        @if ($guide->estimated_minutes)
            <li><strong>Durée :</strong> {{ __('site.resources.estimated_time', ['count' => $guide->estimated_minutes]) }}</li>
        @endif

        @if ($guide->reviewed_on)
            <li>
                <strong>Mise à jour :</strong>
                {{ $guide->reviewed_on->locale('fr')->isoFormat('D MMMM YYYY') }}
            </li>
        @endif
    </ul>

    @if ($guide->prerequisites)
        <h2>{{ __('site.resources.prerequisites') }}</h2>
        <p>{{ $guide->prerequisites }}</p>
    @endif

    @if ($guide->safety_notice)
        <h2>{{ __('site.resources.safety') }}</h2>
        <p><strong>{{ $guide->safety_notice }}</strong></p>
    @endif

    @if ($guide->introduction)
        <div class="prose">{!! nl2br(e($guide->introduction)) !!}</div>
    @endif

    @if ($guide->steps->isNotEmpty())
        <h2>{{ __('site.resources.steps_title') }}</h2>

        <ol class="guide-steps">
            @foreach ($guide->steps as $step)
                <li class="guide-steps__item">
                    <div>
                        <h3 class="guide-steps__title">{{ $step->title }}</h3>
                        <div class="prose">{!! nl2br(e($step->body)) !!}</div>

                        @if ($step->tip)
                            <p><em>{{ __('site.resources.tip') }} : {{ $step->tip }}</em></p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    @endif

    @if ($guide->conclusion)
        <div class="prose">{!! nl2br(e($guide->conclusion)) !!}</div>
    @endif
@endsection
