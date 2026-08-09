{{--
    Étiquette de statut.

    Le libellé est toujours écrit en toutes lettres : la couleur et la forme
    de la pastille ne font que le renforcer, elles ne le remplacent jamais.
--}}
@props([
    'variant' => 'neutral',
    'square' => false,
])

<span {{ $attributes->merge(['class' => 'badge badge--'.$variant.($square ? ' badge--square' : '')]) }}>
    {{ $slot }}
</span>
