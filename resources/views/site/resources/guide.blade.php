@extends('layouts.site')

@section('title', $guide->meta_title ?: $guide->title)
@section('meta_description', $guide->meta_description ?: $guide->summary)

@section('breadcrumb')
    <x-breadcrumb :items="[
        __('site.resources.title') => route('resources.index'),
        $guide->title => null,
    ]" />
@endsection

@push('head')
    {{-- Une fiche en étapes numérotées correspond au type « HowTo ». --}}
    <x-structured-data :data="\App\Support\StructuredData::howTo($guide)" />
@endpush

@section('content')
    <div class="page-header">
        <div class="container">
            <h1>{{ $guide->title }}</h1>
            <p>{{ $guide->summary }}</p>
        </div>
    </div>

    <section class="section">
        <div class="container container--narrow">
            <ul class="guide-meta" role="list">
                <li><strong>{{ __('site.resources.level') }} :</strong> {{ $guide->level->label() }}</li>

                @if ($guide->estimated_minutes)
                    <li><strong>Durée :</strong> {{ __('site.resources.estimated_time', ['count' => $guide->estimated_minutes]) }}</li>
                @endif

                <li>
                    <strong>Mise à jour :</strong>
                    @if ($guide->reviewed_on)
                        <time datetime="{{ $guide->reviewed_on->toDateString() }}">
                            {{ __('site.resources.updated_on', ['date' => $guide->reviewed_on->locale('fr')->isoFormat('D MMMM YYYY')]) }}
                        </time>
                    @else
                        {{ __('site.resources.never_reviewed') }}
                    @endif
                </li>
            </ul>

            <p style="margin-top: 1rem">
                <a class="btn btn--outline" href="{{ route('resources.guide.print', $guide) }}">
                    <x-icon name="document" class="btn__icon" />
                    {{ __('site.resources.print') }}
                </a>
            </p>

            @if ($guide->safety_notice)
                <div class="callout callout--warning" style="margin-top: 1.5rem">
                    <h2 class="callout__title">
                        <x-icon name="alerte" width="24" height="24" />
                        {{ __('site.resources.safety') }}
                    </h2>
                    <p>{{ $guide->safety_notice }}</p>
                </div>
            @endif

            @if ($guide->introduction)
                <div class="prose" style="margin-top: 2rem">{!! nl2br(e($guide->introduction)) !!}</div>
            @endif

            @if ($guide->prerequisites)
                <section aria-labelledby="prerequis" style="margin-top: 2rem">
                    <h2 id="prerequis">{{ __('site.resources.prerequisites') }}</h2>
                    <p>{{ $guide->prerequisites }}</p>
                </section>
            @endif

            @if ($guide->steps->isNotEmpty())
                <section aria-labelledby="etapes" style="margin-top: 2rem">
                    <h2 id="etapes">{{ __('site.resources.steps_title') }}</h2>

                    <ol class="guide-steps" style="margin-top: 1.5rem">
                        @foreach ($guide->steps as $step)
                            <li class="guide-steps__item">
                                <div>
                                    <h3 class="guide-steps__title">{{ $step->title }}</h3>

                                    <div class="prose">{!! nl2br(e($step->body)) !!}</div>

                                    @if ($step->image_path)
                                        <img
                                            src="{{ \Illuminate\Support\Facades\Storage::url($step->image_path) }}"
                                            alt="{{ $step->image_alt }}"
                                            loading="lazy"
                                            style="margin-top: 1rem; border-radius: 8px"
                                        >
                                    @endif

                                    @if ($step->tip)
                                        <p class="callout" style="margin-top: 1rem">
                                            <strong>{{ __('site.resources.tip') }} :</strong> {{ $step->tip }}
                                        </p>
                                    @endif
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </section>
            @endif

            @if ($guide->conclusion)
                <div class="prose" style="margin-top: 2rem">{!! nl2br(e($guide->conclusion)) !!}</div>
            @endif

            @if ($guide->files->isNotEmpty())
                <section aria-labelledby="documents-fiche" style="margin-top: 2rem">
                    <h2 id="documents-fiche">{{ __('site.resources.documents') }}</h2>

                    <ul role="list">
                        @foreach ($guide->files as $file)
                            <li>
                                <a href="{{ $file->url() }}">
                                    {{ $file->title }}
                                    <span class="text-muted">({{ $file->extension() }}, {{ $file->humanSize() }})</span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <x-phone-cta />

            @if ($related->isNotEmpty())
                <section aria-labelledby="fiches-liees" style="margin-top: 3rem">
                    <h2 id="fiches-liees">{{ __('site.resources.related') }}</h2>

                    <ul class="grid" role="list" style="margin-top: 1rem">
                        @foreach ($related as $other)
                            <li class="card card--linked">
                                <div class="card__body">
                                    <h3 class="card__title">
                                        <a class="card__link" href="{{ route('resources.guide', $other) }}">
                                            {{ $other->title }}
                                        </a>
                                    </h3>
                                    <p>{{ $other->summary }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            <p style="margin-top: 2rem">
                <a class="link-arrow" href="{{ route('resources.index') }}">
                    <span aria-hidden="true">←</span> {{ __('site.resources.back') }}
                </a>
            </p>
        </div>
    </section>
@endsection
