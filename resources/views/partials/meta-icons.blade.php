{{--
    Icônes du site.

    Le format ICO n'est plus nécessaire : tous les navigateurs encore en
    service acceptent un PNG. Il reste déclaré s'il a été fourni, pour les
    très anciennes versions d'Internet Explorer.

    L'icône utilise le monogramme seul quand il existe : à 32 pixels, un logo
    comportant trois lignes de texte devient illisible.
--}}
@if (\App\Support\Branding::hasIcoFavicon())
    <link rel="icon" href="{{ asset('images/favicon.ico') }}" sizes="any">
@endif

<link rel="icon" href="{{ \App\Support\Branding::favicon() }}" type="image/png">
<link rel="apple-touch-icon" href="{{ \App\Support\Branding::favicon() }}">
