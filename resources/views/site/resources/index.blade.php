@extends('layouts.site')

@section('title', __('site.resources.title'))
@section('meta_description', __('site.resources.intro'))

@section('breadcrumb')
    <x-breadcrumb :items="[__('site.resources.title') => null]" />
@endsection

@section('content')
    <div class="page-header">
        <div class="container">
            <h1>{{ __('site.resources.title') }}</h1>
            <p>{{ __('site.resources.intro') }}</p>
        </div>
    </div>

    <section class="section">
        <div class="container">
            <form method="GET" action="{{ route('resources.index') }}" class="filters" role="search">
                <h2 class="visually-hidden">{{ __('site.resources.search_label') }}</h2>

                <x-field
                    name="recherche"
                    type="search"
                    :label="__('site.resources.search_label')"
                    :value="$filters['recherche'] ?? null"
                    :placeholder="__('site.resources.search_placeholder')"
                />

                <x-field
                    name="rubrique"
                    type="select"
                    label="Rubrique"
                    :options="$categories->pluck('name', 'slug')->all()"
                    :value="$filters['rubrique'] ?? null"
                    :empty-option="__('site.resources.category_all')"
                />

                <div class="field">
                    <button type="submit" class="btn btn--primary">{{ __('site.resources.search_submit') }}</button>
                </div>
            </form>

            @if ($guides->isEmpty() && $articles->isEmpty())
                <x-alert variant="info">
                    <p>{{ __('site.resources.empty') }}</p>
                </x-alert>
            @endif

            @if ($guides->isNotEmpty())
                <section aria-labelledby="titre-fiches">
                    <h2 id="titre-fiches">{{ __('site.resources.guides_title') }}</h2>

                    <ul class="grid" role="list" style="margin-top: 1.5rem">
                        @foreach ($guides as $guide)
                            <li class="card card--linked">
                                <div class="card__body">
                                    <x-icon name="document" class="card__icon" />

                                    <h3 class="card__title">
                                        <a class="card__link" href="{{ route('resources.guide', $guide) }}">
                                            {{ $guide->title }}
                                        </a>
                                    </h3>

                                    <p>{{ $guide->summary }}</p>

                                    <ul class="card__meta">
                                        <li>{{ $guide->level->label() }}</li>
                                        @if ($guide->estimated_minutes)
                                            <li>{{ __('site.resources.estimated_time', ['count' => $guide->estimated_minutes]) }}</li>
                                        @endif
                                        @if ($guide->category)
                                            <li>{{ $guide->category->name }}</li>
                                        @endif
                                    </ul>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($articles->isNotEmpty())
                <section aria-labelledby="titre-articles" style="margin-top: 3rem">
                    <h2 id="titre-articles">{{ __('site.resources.articles_title') }}</h2>

                    <ul class="grid" role="list" style="margin-top: 1.5rem">
                        @foreach ($articles as $article)
                            <li class="card card--linked">
                                <div class="card__body">
                                    <h3 class="card__title">
                                        <a class="card__link" href="{{ route('resources.article', $article) }}">
                                            {{ $article->title }}
                                        </a>
                                    </h3>

                                    <p>{{ $article->excerpt }}</p>

                                    <ul class="card__meta">
                                        <li>{{ __('site.resources.reading_time', ['count' => $article->readingMinutes()]) }}</li>
                                        @if ($article->published_at)
                                            <li>
                                                <time datetime="{{ $article->published_at->toDateString() }}">
                                                    {{ $article->published_at->locale('fr')->isoFormat('D MMMM YYYY') }}
                                                </time>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </li>
                        @endforeach
                    </ul>

                    {{ $articles->links() }}
                </section>
            @endif
        </div>
    </section>
@endsection
