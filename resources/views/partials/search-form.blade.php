{{--
    Formulaire de recherche du site public.

    Le libellé reste visible : un champ dont l'invite disparaît à la première
    lettre tapée laisse certaines personnes sans repère sur ce qu'on attend
    d'elles.

    @var string|null $query
    @var string $id  identifiant unique, le formulaire pouvant figurer deux fois
    @var string $button  variante du bouton, selon le fond sur lequel il est posé
--}}
@php
    $id ??= 'recherche';
    $query ??= null;
    $button ??= 'btn--primary';
@endphp

<form method="GET" action="{{ route('search') }}" class="search-form" role="search" aria-labelledby="{{ $id }}-libelle">
    <label class="search-form__label" id="{{ $id }}-libelle" for="{{ $id }}">{{ __('site.search.label') }}</label>

    <div class="search-form__row">
        <input
            class="field__control search-form__input"
            type="search"
            id="{{ $id }}"
            name="q"
            value="{{ $query }}"
            maxlength="150"
            placeholder="{{ __('site.search.placeholder') }}"
            autocomplete="off"
            enterkeyhint="search"
        >

        <button type="submit" class="btn {{ $button }}">{{ __('site.search.submit') }}</button>
    </div>
</form>
