{{--
    Gabarit du site public.

    Points d'attention : liens d'évitement en tête, un seul <h1> par page,
    landmarks explicites (banner, navigation, main, contentinfo), et un
    numéro de téléphone visible sur toutes les tailles d'écran.
--}}
@php
    $settings = app(\App\Services\SettingsService::class);
    $nonce = request()->attributes->get('csp_nonce');
    $analyticsActive = config('site.analytics.enabled') && filled(config('site.analytics.matomo_url'));
@endphp

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#255c75">

    <title>@yield('title', $settings->string('site_name')) — {{ $settings->string('site_name') }}</title>

    <meta name="description" content="@yield('meta_description', $settings->string('site_tagline'))">
    <link rel="canonical" href="{{ url()->current() }}">

    @hasSection('noindex')
        <meta name="robots" content="noindex, follow">
    @endif

    @include('partials.meta-icons')

    {{-- Open Graph : aperçu correct lorsqu'un lien est partagé --}}
    <meta property="og:type" content="website">
    <meta property="og:locale" content="fr_FR">
    <meta property="og:site_name" content="{{ $settings->string('site_name') }}">
    <meta property="og:title" content="@yield('title', $settings->string('site_name'))">
    <meta property="og:description" content="@yield('meta_description', $settings->string('site_tagline'))">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:image" content="{{ asset('images/og-image.png') }}">
    <meta property="og:image:alt" content="{{ $settings->string('site_name') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">

    {{-- L'illustration est au format 1200 × 630 : le grand aperçu est possible. --}}
    <meta name="twitter:card" content="summary_large_image">

    {{--
        Les préférences d'affichage sont appliquées avant le premier rendu :
        sans cela, une personne en contraste renforcé verrait apparaître un
        instant la version normale.
    --}}
    <script @if ($nonce) nonce="{{ $nonce }}" @endif>
        (function () {
            try {
                var p = JSON.parse(localStorage.getItem('cn.a11y') || '{}');
                var r = document.documentElement;
                if (p.contrast === 'high') r.setAttribute('data-contrast', 'high');
                if (p.textSize && p.textSize !== 'normal') r.setAttribute('data-text-size', p.textSize);
                if (p.motion && p.motion !== 'auto') r.setAttribute('data-motion', p.motion);
            } catch (e) {}
        })();
    </script>

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])

    @stack('head')
</head>
<body class="{{ $settings->hasPhone() ? 'has-call-bar' : '' }}">
    <div class="skip-links">
        <a class="skip-link" href="#contenu">{{ __('site.skip.content') }}</a>
        <a class="skip-link" href="#menu-principal">{{ __('site.skip.nav') }}</a>
        <a class="skip-link" href="#pied-de-page">{{ __('site.skip.footer') }}</a>
    </div>

    {{-- Zone d'annonces pour les lecteurs d'écran (changement de réglage) --}}
    <div class="sr-live" id="a11y-announcer" role="status" aria-live="polite"></div>

    @include('partials.a11y-bar')
    @include('partials.header')

    @hasSection('breadcrumb')
        @yield('breadcrumb')
    @endif

    <main id="contenu" tabindex="-1">
        @if (session('status'))
            <div class="container" style="margin-top: 1.5rem">
                <x-alert :variant="session('status_variant', 'success')">
                    <p>{{ session('status') }}</p>
                </x-alert>
            </div>
        @endif

        @yield('content')
    </main>

    @include('partials.footer')

    {{--
        Le numéro est affiché en toutes lettres, et non un simple « Appeler » :
        l'en-tête défile avec la page, cette barre est donc le seul endroit où
        le numéro reste visible en permanence sur mobile (codex §35).
    --}}
    @if ($settings->hasPhone())
        <div class="call-bar no-print">
            <a class="call-bar__link" href="{{ $settings->phoneLink() }}">
                <x-icon name="telephone" width="24" height="24" />
                <span class="call-bar__verb">{{ __('site.call.label') }}</span>
                <span>{{ $settings->phoneDisplay() }}</span>
            </a>
        </div>
    @endif

    @if ($analyticsActive)
        <div
            class="cookie-banner no-print"
            data-cookie-banner
            data-analytics-url="{{ config('site.analytics.matomo_url') }}"
            data-analytics-site-id="{{ config('site.analytics.matomo_site_id') }}"
            role="dialog"
            aria-labelledby="cookies-titre"
            hidden
        >
            <div class="cookie-banner__inner">
                <div>
                    <h2 id="cookies-titre">{{ __('site.cookies.title') }}</h2>
                    <p>{{ __('site.cookies.text') }}</p>
                    <p><a href="{{ route('cookies') }}">{{ __('site.cookies.more') }}</a></p>
                </div>

                <div class="cookie-banner__actions">
                    <button type="button" class="btn btn--outline" data-cookie-refuse>
                        {{ __('site.cookies.refuse') }}
                    </button>
                    <button type="button" class="btn btn--primary" data-cookie-accept>
                        {{ __('site.cookies.accept') }}
                    </button>
                </div>
            </div>
        </div>
    @endif

    @stack('scripts')
</body>
</html>
