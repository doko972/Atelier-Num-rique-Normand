@extends('layouts.site')

@section('title', $category->meta_title ?: $category->name)
@section('meta_description', $category->meta_description ?: $category->summary)

@section('breadcrumb')
    <x-breadcrumb :items="[
        __('site.services.title') => route('services.index'),
        $category->name => null,
    ]" />
@endsection

@section('content')
    <div class="page-header">
        <div class="container">
            <h1>{{ $category->name }}</h1>

            @if ($category->summary)
                <p>{{ $category->summary }}</p>
            @endif
        </div>
    </div>

    <section class="section">
        <div class="container">
            @if ($category->description)
                <div class="prose" style="margin-bottom: 2rem">
                    {!! nl2br(e($category->description)) !!}
                </div>
            @endif

            <h2>{{ __('site.services.category_services') }}</h2>

            @if ($services->isEmpty())
                <p>{{ __('site.services.empty') }}</p>
            @else
                <ul class="grid" role="list" style="margin-top: 1.5rem">
                    @foreach ($services as $service)
                        <li class="card card--linked">
                            <div class="card__body">
                                <x-icon :name="$service->icon ?: $category->icon ?: 'aide'" class="card__icon" />

                                <h3 class="card__title">
                                    <a class="card__link" href="{{ route('services.detail', [$category, $service]) }}">
                                        {{ $service->title }}
                                    </a>
                                </h3>

                                <p>{{ $service->summary }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>

    @if ($otherCategories->isNotEmpty())
        <section class="section section--sunken" aria-labelledby="autres-familles">
            <div class="container">
                <h2 id="autres-familles">{{ __('site.services.other_categories') }}</h2>

                <ul class="coverage" role="list" style="margin-top: 1rem">
                    @foreach ($otherCategories as $other)
                        <li>
                            <a class="coverage__item" href="{{ route('services.show', $other) }}">{{ $other->name }}</a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    <section class="section section--secondary">
        <div class="container container--narrow">
            <x-phone-cta />
        </div>
    </section>
@endsection
