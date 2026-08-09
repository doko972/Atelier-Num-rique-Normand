@extends('layouts.site')

@section('title', $page->meta_title ?: $page->title)
@section('meta_description', $page->meta_description ?: $page->summary)

@if ($page->noindex)
    @section('noindex', true)
@endif

@section('breadcrumb')
    <x-breadcrumb :items="[$page->title => null]" />
@endsection

@section('content')
    <div class="page-header">
        <div class="container container--narrow">
            <h1>{{ $page->title }}</h1>

            @if ($page->summary)
                <p>{{ $page->summary }}</p>
            @endif
        </div>
    </div>

    <section class="section">
        <div class="container container--narrow">
            <div class="prose">{!! nl2br(e($page->body)) !!}</div>

            @if ($page->files->isNotEmpty())
                <section aria-labelledby="documents-page" style="margin-top: 2rem">
                    <h2 id="documents-page">{{ __('site.resources.documents') }}</h2>

                    <ul role="list">
                        @foreach ($page->files as $file)
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

            <p class="text-small text-muted" style="margin-top: 2rem">
                Dernière mise à jour :
                <time datetime="{{ $page->updated_at?->toDateString() }}">
                    {{ $page->updated_at?->locale('fr')->isoFormat('D MMMM YYYY') }}
                </time>
            </p>
        </div>
    </section>
@endsection
