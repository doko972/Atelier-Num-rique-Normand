@extends('layouts.site')

@php $settings = app(\App\Services\SettingsService::class); @endphp

@section('title', $page->meta_title ?: $page->title)
@section('meta_description', $page->meta_description ?: $page->summary)

@section('breadcrumb')
    <x-breadcrumb :items="[$page->title => null]" />
@endsection

@push('head')
    <x-structured-data :data="\App\Support\StructuredData::person($settings)" />
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
            <div class="prose">{!! nl2br(e($page->body)) !!}</div>

            <blockquote style="margin-top: 2rem">
                <p>
                    Mon objectif n’est pas de faire à votre place, mais de vous permettre
                    de comprendre et de refaire seul, en toute confiance.
                </p>
            </blockquote>
        </div>
    </section>

    @if ($testimonials->isNotEmpty())
        <section class="section section--sunken" aria-labelledby="temoignages-apropos">
            <div class="container">
                <h2 id="temoignages-apropos">{{ __('site.about.testimonials_title') }}</h2>

                <ul class="grid" role="list" style="margin-top: 1.5rem">
                    @foreach ($testimonials as $testimonial)
                        <li>
                            <figure class="testimonial">
                                <blockquote class="testimonial__quote" style="border: 0; background: none; padding: 0">
                                    {{ $testimonial->quote }}
                                </blockquote>
                                <figcaption class="testimonial__author">{{ $testimonial->attribution() }}</figcaption>
                            </figure>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>
    @endif

    <section class="section">
        <div class="container container--narrow">
            <x-phone-cta />
        </div>
    </section>
@endsection
