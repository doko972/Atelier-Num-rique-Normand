@extends('layouts.site')

@section('title', __('site.workshops.title'))
@section('meta_description', __('site.workshops.intro'))

@section('breadcrumb')
    <x-breadcrumb :items="[__('site.workshops.title') => null]" />
@endsection

@section('content')
    <div class="page-header">
        <div class="container">
            <h1>{{ __('site.workshops.title') }}</h1>
            <p>{{ __('site.workshops.intro') }}</p>
        </div>
    </div>

    <section class="section">
        <div class="container">

            {{-- Filtres : formulaire GET simple, fonctionne sans JavaScript --}}
            <form method="GET" action="{{ route('workshops.index') }}" class="filters" role="search">
                <h2 class="visually-hidden">{{ __('site.workshops.filters.title') }}</h2>

                <x-field
                    name="categorie"
                    type="select"
                    :label="__('site.workshops.filters.category')"
                    :options="$categories->pluck('name', 'slug')->all()"
                    :value="$filters['categorie'] ?? null"
                    :empty-option="__('site.workshops.filters.all')"
                />

                <x-field
                    name="commune"
                    type="select"
                    :label="__('site.workshops.filters.municipality')"
                    :options="$municipalities->pluck('name', 'slug')->all()"
                    :value="$filters['commune'] ?? null"
                    :empty-option="__('site.workshops.filters.all')"
                />

                <div class="field">
                    <button type="submit" class="btn btn--primary">
                        {{ __('site.workshops.filters.submit') }}
                    </button>
                </div>

                @if (array_filter($filters))
                    <div class="field">
                        <a class="btn btn--ghost" href="{{ route('workshops.index') }}">
                            {{ __('site.workshops.filters.reset') }}
                        </a>
                    </div>
                @endif
            </form>

            @if ($workshops->isEmpty())
                <x-alert variant="info">
                    <p>{{ __('site.workshops.empty') }}</p>
                </x-alert>
            @else
                <p class="text-muted">{{ __('admin.common.results', ['count' => $workshops->total()]) }}</p>

                <div class="stack" style="margin-top: 1rem">
                    @foreach ($workshops as $workshop)
                        <x-workshop-card :workshop="$workshop" />
                    @endforeach
                </div>

                {{ $workshops->links() }}
            @endif
        </div>
    </section>

    @if ($pastWorkshops->isNotEmpty())
        <section class="section section--sunken" aria-labelledby="ateliers-passes">
            <div class="container">
                <h2 id="ateliers-passes">{{ __('site.workshops.past') }}</h2>

                <ul class="grid" role="list" style="margin-top: 1rem">
                    @foreach ($pastWorkshops as $workshop)
                        <li class="card">
                            <div class="card__body">
                                <h3 class="card__title">{{ $workshop->title }}</h3>
                                <p class="text-muted text-small">
                                    {{ $workshop->date->locale('fr')->isoFormat('D MMMM YYYY') }}
                                </p>
                            </div>
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
