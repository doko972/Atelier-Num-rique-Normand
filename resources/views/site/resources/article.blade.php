@extends('layouts.site')

@section('title', $article->meta_title ?: $article->title)
@section('meta_description', $article->meta_description ?: $article->excerpt)

@section('breadcrumb')
    <x-breadcrumb :items="[
        __('site.resources.title') => route('resources.index'),
        $article->title => null,
    ]" />
@endsection

@push('head')
    <x-structured-data :data="\App\Support\StructuredData::article($article)" />
@endpush

@section('content')
    <div class="page-header">
        <div class="container container--narrow">
            <h1>{{ $article->title }}</h1>
            <p>{{ $article->excerpt }}</p>
        </div>
    </div>

    <section class="section">
        <article class="container container--narrow">
            <ul class="guide-meta" role="list">
                @if ($article->published_at)
                    <li>
                        <time datetime="{{ $article->published_at->toDateString() }}">
                            {{ $article->published_at->locale('fr')->isoFormat('D MMMM YYYY') }}
                        </time>
                    </li>
                @endif

                <li>{{ __('site.resources.reading_time', ['count' => $article->readingMinutes()]) }}</li>

                @if ($article->category)
                    <li>{{ $article->category->name }}</li>
                @endif
            </ul>

            <div class="prose" style="margin-top: 2rem">{!! nl2br(e($article->body)) !!}</div>

            @if ($article->files->isNotEmpty())
                <section aria-labelledby="documents-article" style="margin-top: 2rem">
                    <h2 id="documents-article">{{ __('site.resources.documents') }}</h2>

                    <ul role="list">
                        @foreach ($article->files as $file)
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
                <section aria-labelledby="articles-lies" style="margin-top: 3rem">
                    <h2 id="articles-lies">{{ __('site.resources.related') }}</h2>

                    <ul class="grid" role="list" style="margin-top: 1rem">
                        @foreach ($related as $other)
                            <li class="card card--linked">
                                <div class="card__body">
                                    <h3 class="card__title">
                                        <a class="card__link" href="{{ route('resources.article', $other) }}">
                                            {{ $other->title }}
                                        </a>
                                    </h3>
                                    <p>{{ $other->excerpt }}</p>
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
        </article>
    </section>
@endsection
