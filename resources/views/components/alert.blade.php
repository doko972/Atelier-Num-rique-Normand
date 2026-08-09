{{--
    Message de succès, d'erreur ou d'information.

    L'icône double l'information portée par la couleur (RGAA), et role="alert"
    fait annoncer le message par les lecteurs d'écran dès son apparition.
--}}
@props([
    'variant' => 'info',
    'title' => null,
])

@php
    $icons = [
        'success' => '✓',
        'warning' => '!',
        'danger' => '⚠',
        'info' => 'i',
    ];

    $roles = [
        'danger' => 'alert',
        'warning' => 'alert',
    ];
@endphp

<div
    {{ $attributes->merge(['class' => 'alert alert--'.$variant]) }}
    role="{{ $roles[$variant] ?? 'status' }}"
>
    <span class="alert__icon" aria-hidden="true">{{ $icons[$variant] ?? 'i' }}</span>

    <div class="alert__body">
        @if ($title)
            <p class="alert__title">{{ $title }}</p>
        @endif

        {{ $slot }}
    </div>
</div>
