{{--
    Logo du service.

    L'image est décorative : le nom du service figure toujours en texte juste
    à côté. Un `alt` répétant ce nom le ferait annoncer deux fois par un
    lecteur d'écran.

    La déclinaison demandée se rabat sur le logo complet si son fichier n'a
    pas encore été fourni — aucune image cassée n'est donc possible.
--}}
@props([
    'variant' => \App\Support\Branding::FULL,
    'width' => 40,
    'height' => 40,
])

<img
    {{ $attributes->merge(['class' => 'site-logo']) }}
    src="{{ \App\Support\Branding::logo($variant) }}"
    alt=""
    role="presentation"
    width="{{ $width }}"
    height="{{ $height }}"
    decoding="async"
>
