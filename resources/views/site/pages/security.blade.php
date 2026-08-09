@extends('layouts.site')

@section('title', $page->meta_title ?: $page->title)
@section('meta_description', $page->meta_description ?: $page->summary)

@section('breadcrumb')
    <x-breadcrumb :items="[$page->title => null]" />
@endsection

@push('head')
    <x-structured-data :data="\App\Support\StructuredData::faqPage($faqs)" />
@endpush

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
            {{-- Encart exigé par le codex §13, non modifiable depuis l'éditeur. --}}
            <div class="callout callout--danger">
                <h2 class="callout__title">
                    <x-icon name="alerte" width="28" height="28" />
                    En cas de doute
                </h2>
                <p style="font-size: 1.125rem"><strong>{{ __('site.security.warning') }}</strong></p>
            </div>

            <div class="prose" style="margin-top: 2rem">{!! nl2br(e($page->body)) !!}</div>
        </div>
    </section>

    @if ($links->isNotEmpty())
        <section class="section section--surface" aria-labelledby="liens-securite">
            <div class="container container--narrow">
                <h2 id="liens-securite">{{ __('site.security.links_title') }}</h2>

                <ul class="stack--sm stack" role="list" style="margin-top: 1.5rem">
                    @foreach ($links as $link)
                        <li class="card">
                            <div class="card__body">
                                <h3 class="card__title">
                                    <a href="{{ $link->url }}" rel="noopener noreferrer" target="_blank">
                                        {{ $link->label }}
                                        <span class="visually-hidden">({{ __('site.common.new_window') }}, {{ __('site.common.external_link') }})</span>
                                    </a>
                                </h3>

                                @if ($link->description)
                                    <p>{{ $link->description }}</p>
                                @endif

                                <p class="text-small text-muted">{{ $link->host() }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    @if ($faqs->isNotEmpty())
        <section class="section" aria-labelledby="faq-securite">
            <div class="container container--narrow">
                <h2 id="faq-securite">{{ __('site.security.faq_title') }}</h2>

                <div class="faq" style="margin-top: 1.5rem">
                    @foreach ($faqs as $faq)
                        <details class="faq__item">
                            <summary class="faq__question">{{ $faq->question }}</summary>
                            <div class="faq__answer">{!! nl2br(e($faq->answer)) !!}</div>
                        </details>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="section section--secondary">
        <div class="container container--narrow">
            <x-phone-cta />
        </div>
    </section>
@endsection
