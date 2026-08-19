@extends('layouts.site')

@section('title', $page->meta_title ?: $page->title)
@section('meta_description', $page->meta_description ?: $page->summary)

@section('breadcrumb')
    <x-breadcrumb :items="[$page->title => null]" />
@endsection

@section('content')
    {{--
        Les mentions légales sont la seule page dont le contenu est imposé par
        la loi. Elle est donc construite à partir des paramètres du site plutôt
        que d'un texte libre : une coordonnée corrigée dans le back-office doit
        l'être ici sans qu'on ait à repasser derrière.
    --}}
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
            <h2>Éditeur du site</h2>

            <dl class="legal-identity">
                @if ($settings->string('adviser_name'))
                    <dt>Éditeur</dt>
                    <dd>{{ $settings->string('adviser_name') }}</dd>
                @endif

                @if ($settings->string('legal_status'))
                    <dt>Forme juridique</dt>
                    <dd>{{ $settings->string('legal_status') }}</dd>
                @endif

                @if ($address)
                    <dt>Adresse</dt>
                    <dd>{{ $address }}</dd>
                @endif

                @if ($settings->string('siret'))
                    <dt>SIRET</dt>
                    <dd>{{ $settings->string('siret') }}</dd>
                @endif

                @if ($settings->hasPhone())
                    <dt>Téléphone</dt>
                    <dd><a href="{{ $settings->phoneLink() }}">{{ $settings->phoneDisplay() }}</a></dd>
                @endif

                @if ($settings->string('email'))
                    <dt>Adresse électronique</dt>
                    <dd><a href="mailto:{{ $settings->string('email') }}">{{ $settings->string('email') }}</a></dd>
                @endif

                @if ($settings->string('publication_director'))
                    <dt>Directeur de la publication</dt>
                    <dd>{{ $settings->string('publication_director') }}</dd>
                @endif
            </dl>

            <h2>Hébergement</h2>

            @if ($settings->string('hosting_provider'))
                <p class="legal-host">{!! nl2br(e($settings->string('hosting_provider'))) !!}</p>
            @else
                {{--
                    Visible en développement seulement : sur le site publié, une
                    mention d'hébergeur absente est un manquement à la loi, pas
                    une information à donner au visiteur.
                --}}
                @if (config('app.debug'))
                    <x-alert variant="warning">
                        <p>Hébergeur non renseigné dans les paramètres du site.</p>
                    </x-alert>
                @endif
            @endif

            <div class="prose" style="margin-top: 2rem">{!! nl2br(e($page->body)) !!}</div>

            <p class="text-small text-muted" style="margin-top: 2rem">
                Dernière mise à jour :
                <time datetime="{{ $page->updated_at?->toDateString() }}">
                    {{ $page->updated_at?->locale('fr')->isoFormat('D MMMM YYYY') }}
                </time>
            </p>
        </div>
    </section>
@endsection
