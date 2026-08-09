{{--
    Gabarit des documents destinés au papier : fiches pratiques, plaquette
    grand public, présentation professionnelle.

    La barre d'outils et la feuille partagent la même largeur, fixée par le
    format déclaré par la page (`page-format`). Sans cela, le bouton se
    retrouve décalé par rapport au document sur lequel il agit.
--}}
@php $settings = app(\App\Services\SettingsService::class); @endphp

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, follow">

    <title>@yield('title')</title>

    @include('partials.meta-icons')

    @vite(['resources/sass/app.scss'])
</head>
<body class="print-body">
    <main class="print-page print-page--@yield('page-format', 'a4')" id="contenu">
        <div class="print-toolbar no-print">
            <button type="button" class="btn btn--primary" onclick="window.print()">
                {{ __('site.resources.print') }}
            </button>

            <a class="btn btn--outline" href="{{ url()->previous() }}">
                {{ __('site.common.back') }}
            </a>
        </div>

        {{-- La feuille : fond blanc et ombre légère, pour se lire comme du papier. --}}
        <div class="print-sheet printable">
            @yield('content')

            <div class="print-contact">
                <p><strong>{{ $settings->string('site_name') }}</strong></p>

                @if ($settings->hasPhone())
                    <p>{{ __('site.call.label') }} {{ $settings->phoneDisplay() }}</p>
                @endif

                <p>{{ config('app.url') }}</p>
            </div>
        </div>

        @hasSection('after-sheet')
            <div class="print-notes no-print">
                @yield('after-sheet')
            </div>
        @endif
    </main>
</body>
</html>
