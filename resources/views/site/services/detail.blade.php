@extends('layouts.site')

@section('title', $service->meta_title ?: $service->title)
@section('meta_description', $service->meta_description ?: $service->summary)

@section('breadcrumb')
    <x-breadcrumb :items="[
        __('site.services.title') => route('services.index'),
        $category->name => route('services.show', $category),
        $service->title => null,
    ]" />
@endsection

@push('head')
    <x-structured-data :data="\App\Support\StructuredData::service($category, $service)" />
@endpush

@section('content')
    <div class="page-header">
        <div class="container">
            <h1>{{ $service->title }}</h1>
            <p>{{ $service->summary }}</p>
        </div>
    </div>

    <section class="section">
        <div class="container">
            <div class="split--aside split">
                <div>
                    @if ($service->description)
                        <div class="prose">{!! nl2br(e($service->description)) !!}</div>
                    @endif

                    @if (filled($service->learning_points))
                        <h2 style="margin-top: 2rem">{{ __('site.services.learning_points') }}</h2>

                        <ul class="check-list check-list--yes" role="list" style="margin-top: 1rem">
                            @foreach ($service->learning_points as $point)
                                <li>{{ $point }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>

                <aside class="stack">
                    <div class="callout">
                        <h2 class="callout__title">Bon à savoir</h2>

                        <ul class="card__meta" style="flex-direction: column; align-items: flex-start">
                            <li><strong>{{ __('site.services.level') }} :</strong> {{ $service->level->label() }}</li>

                            @if ($service->estimated_duration_minutes)
                                <li>
                                    <strong>{{ __('site.services.duration') }} :</strong>
                                    {{ __('site.resources.estimated_time', ['count' => $service->estimated_duration_minutes]) }}
                                </li>
                            @endif
                        </ul>

                        <p>
                            <a class="btn btn--accent btn--block" href="{{ route('appointments.create') }}">
                                {{ __('site.cta.appointment') }}
                            </a>
                        </p>
                    </div>

                    <x-phone-cta />
                </aside>
            </div>

            @if ($siblings->isNotEmpty())
                <section aria-labelledby="services-proches" style="margin-top: 3rem">
                    <h2 id="services-proches">Dans la même famille</h2>

                    <ul class="grid" role="list" style="margin-top: 1rem">
                        @foreach ($siblings as $sibling)
                            <li class="card card--linked">
                                <div class="card__body">
                                    <h3 class="card__title">
                                        <a class="card__link" href="{{ route('services.detail', [$category, $sibling]) }}">
                                            {{ $sibling->title }}
                                        </a>
                                    </h3>
                                    <p>{{ $sibling->summary }}</p>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif
        </div>
    </section>
@endsection
