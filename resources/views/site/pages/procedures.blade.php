@extends('layouts.site')

@section('title', $page->meta_title ?: $page->title)
@section('meta_description', $page->meta_description ?: $page->summary)

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
        </div>
    </section>

    {{-- Les règles de sécurité sont codées en dur : elles ne doivent jamais
         pouvoir disparaître d'une modification éditoriale. --}}
    <section class="section section--surface" aria-labelledby="regles">
        <div class="container container--narrow">
            <h2 id="regles">{{ __('site.procedures.rules_title') }}</h2>

            <ul class="check-list check-list--no" role="list" style="margin-top: 1.5rem">
                <li>{{ __('site.procedures.rules.no_password_email') }}</li>
                <li>{{ __('site.procedures.rules.no_bank_code') }}</li>
                <li>{{ __('site.procedures.rules.no_sms_code') }}</li>
            </ul>

            <ul class="check-list check-list--yes" role="list" style="margin-top: 1rem">
                <li>{{ __('site.procedures.rules.stay_actor') }}</li>
                <li>{{ __('site.procedures.rules.personal_only') }}</li>
                <li>{{ __('site.procedures.rules.no_storage') }}</li>
            </ul>
        </div>
    </section>

    @if ($links->isNotEmpty())
        <section class="section" aria-labelledby="liens-officiels">
            <div class="container container--narrow">
                <h2 id="liens-officiels">{{ __('site.procedures.links_title') }}</h2>

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

                                {{-- Le domaine est affiché : une personne prudente peut
                                     vérifier vers où le lien l'envoie avant de cliquer. --}}
                                <p class="text-small text-muted">{{ $link->host() }}</p>
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
