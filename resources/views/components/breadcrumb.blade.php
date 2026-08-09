{{--
    Fil d'Ariane (codex §6), doublé de données structurées BreadcrumbList.

    $items : tableau [libellé => url|null]. Le dernier élément, sans lien,
    porte aria-current="page".
--}}
@props(['items' => []])

@php
    $all = array_merge([__('site.breadcrumb.home') => route('home')], $items);
    $keys = array_keys($all);
    $lastKey = end($keys);
@endphp

<nav class="breadcrumb no-print" aria-label="{{ __('site.breadcrumb.label') }}">
    <ol class="breadcrumb__list">
        @foreach ($all as $label => $url)
            <li class="breadcrumb__item">
                @if ($url && $label !== $lastKey)
                    <a class="breadcrumb__link" href="{{ $url }}">{{ $label }}</a>
                @else
                    <span class="breadcrumb__current" aria-current="page">{{ $label }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>

<x-structured-data :data="\App\Support\StructuredData::breadcrumb($all)" />
