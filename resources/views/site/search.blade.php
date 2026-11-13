@extends('layouts.site')

@php
    $title = $query === '' ? __('site.search.title') : __('site.search.results_title', ['query' => $query]);
@endphp

@section('title', $title)
@section('meta_description', __('site.search.intro'))

{{-- Une page de résultats n'a rien à faire dans un moteur de recherche. --}}
@section('noindex', true)

@section('breadcrumb')
    <x-breadcrumb :items="[__('site.search.title') => null]" />
@endsection

@section('content')
    <div class="page-header">
        <div class="container container--narrow">
            <h1>{{ $title }}</h1>
            <p>{{ __('site.search.intro') }}</p>

            {{-- Bandeau foncé : le bouton principal s'y fondrait. --}}
            @include('partials.search-form', ['query' => $query, 'id' => 'recherche-page', 'button' => 'btn--accent'])
        </div>
    </div>

    <section class="section">
        <div class="container container--narrow">
            @if ($query !== '' && $results->isNotEmpty())
                {{-- Annoncé aux lecteurs d'écran dès l'arrivée sur la page. --}}
                <p role="status">{{ trans_choice('site.search.count', $results->count(), ['count' => $results->count()]) }}</p>

                <ol class="search-results" role="list">
                    @foreach ($results as $result)
                        <li class="search-result">
                            <p class="search-result__type">{{ __("site.search.types.{$result['type']}") }}</p>
                            <h2 class="search-result__title">
                                <a href="{{ $result['url'] }}">{{ $result['title'] }}</a>
                            </h2>
                            @if ($result['summary'] !== '')
                                <p class="search-result__summary">{{ $result['summary'] }}</p>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @else
                @if ($query !== '')
                    <div role="status">
                        <h2>{{ __('site.search.none_title', ['query' => $query]) }}</h2>
                        <p>{{ __('site.search.none_text') }}</p>
                    </div>

                    <x-phone-cta :text="__('site.search.phone_text')" />
                @endif

                <div class="split" style="margin-top: 2rem">
                    <div>
                        <h2>{{ __('site.search.examples_title') }}</h2>
                        <ul class="search-examples" role="list">
                            @foreach (__('site.search.examples') as $example)
                                <li><a href="{{ route('search', ['q' => $example]) }}">{{ $example }}</a></li>
                            @endforeach
                        </ul>
                    </div>

                    <div>
                        <h2>{{ __('site.search.browse_title') }}</h2>
                        <ul class="search-examples" role="list">
                            <li><a href="{{ route('services.index') }}">{{ __('site.nav.services') }}</a></li>
                            <li><a href="{{ route('procedures') }}">{{ __('site.nav.procedures') }}</a></li>
                            <li><a href="{{ route('security') }}">{{ __('site.nav.security') }}</a></li>
                            <li><a href="{{ route('resources.index') }}">{{ __('site.nav.resources') }}</a></li>
                            <li><a href="{{ route('workshops.index') }}">{{ __('site.nav.workshops') }}</a></li>
                            <li><a href="{{ route('faq') }}">{{ __('site.nav.faq') }}</a></li>
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
