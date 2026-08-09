@extends('layouts.site')

@section('title', __('site.services.title'))
@section('meta_description', __('site.services.intro'))

@section('breadcrumb')
    <x-breadcrumb :items="[__('site.services.title') => null]" />
@endsection

@section('content')
    <div class="page-header">
        <div class="container">
            <h1>{{ __('site.services.title') }}</h1>
            <p>{{ __('site.services.intro') }}</p>
        </div>
    </div>

    <section class="section">
        <div class="container">
            @if ($categories->isEmpty())
                <p>{{ __('site.services.empty') }}</p>
            @else
                <div class="stack--lg stack">
                    @foreach ($categories as $category)
                        <section aria-labelledby="famille-{{ $category->slug }}">
                            <div class="section__header">
                                <h2 id="famille-{{ $category->slug }}">
                                    <a href="{{ route('services.show', $category) }}">{{ $category->name }}</a>
                                </h2>

                                @if ($category->summary)
                                    <p>{{ $category->summary }}</p>
                                @endif
                            </div>

                            @if ($category->services->isEmpty())
                                <p class="text-muted">{{ __('site.services.empty') }}</p>
                            @else
                                <ul class="grid" role="list">
                                    @foreach ($category->services as $service)
                                        <li class="card card--linked">
                                            <div class="card__body">
                                                <x-icon :name="$service->icon ?: $category->icon ?: 'aide'" class="card__icon" />

                                                <h3 class="card__title">
                                                    <a class="card__link" href="{{ route('services.detail', [$category, $service]) }}">
                                                        {{ $service->title }}
                                                    </a>
                                                </h3>

                                                <p>{{ $service->summary }}</p>

                                                <ul class="card__meta">
                                                    <li>{{ $service->level->label() }}</li>
                                                    @if ($service->estimated_duration_minutes)
                                                        <li>{{ __('site.resources.estimated_time', ['count' => $service->estimated_duration_minutes]) }}</li>
                                                    @endif
                                                </ul>
                                            </div>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </section>
                    @endforeach
                </div>
            @endif
        </div>
    </section>

    <section class="section section--secondary">
        <div class="container container--narrow">
            <x-phone-cta />
        </div>
    </section>
@endsection
