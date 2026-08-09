<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">

    <title>@yield('title') — {{ app(\App\Services\SettingsService::class)->string('site_name') }}</title>

    @include('partials.meta-icons')

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>
<body>
    <main class="auth-page" id="contenu">
        <div class="auth-card">
            @yield('content')
        </div>
    </main>
</body>
</html>
