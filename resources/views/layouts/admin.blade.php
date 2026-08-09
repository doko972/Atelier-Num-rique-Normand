{{--
    Gabarit du back-office.

    Menu latéral organisé par usage réel (« au quotidien » d'abord), et
    compteurs sur les entrées qui demandent une action.
--}}
@php
    $settings = app(\App\Services\SettingsService::class);
    $user = auth()->user();
@endphp

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title', __('admin.title')) — {{ $settings->string('site_name') }}</title>

    @include('partials.meta-icons')

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <div class="skip-links">
        <a class="skip-link" href="#contenu">{{ __('site.skip.content') }}</a>
    </div>

    <div class="sr-live" id="a11y-announcer" role="status" aria-live="polite"></div>

    <div class="admin">
        @include('partials.admin-sidebar')

        <div class="admin-main">
            <div class="admin-topbar no-print">
                <a href="{{ route('home') }}">{{ __('admin.nav.view_site') }}</a>

                <div class="admin-topbar__user">
                    <span>{{ $user->name }}</span>
                    <x-badge :variant="$user->roleEnum()?->badgeVariant() ?? 'neutral'">
                        {{ $user->roleEnum()?->label() }}
                    </x-badge>

                    <a class="btn btn--ghost btn--sm" href="{{ route('admin.profile.edit') }}">
                        {{ __('admin.nav.profile') }}
                    </a>

                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="btn btn--outline btn--sm">
                            {{ __('admin.nav.logout') }}
                        </button>
                    </form>
                </div>
            </div>

            <main class="admin-content" id="contenu" tabindex="-1">
                @if (session('status'))
                    <x-alert :variant="session('status_variant', 'success')">
                        <p>{{ session('status') }}</p>
                    </x-alert>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
