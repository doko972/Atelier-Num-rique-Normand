{{--
    Protection anti-spam accessible (codex §25).

    Aucun captcha : les tests visuels ou sonores excluent précisément le
    public de ce site. À la place, un champ leurre invisible — retiré de
    l'arbre d'accessibilité par aria-hidden et tabindex="-1", donc jamais
    annoncé ni atteint au clavier — et l'horodatage d'ouverture du formulaire.
--}}
@php
    $honeypot = \App\Http\Requests\Site\PublicFormRequest::HONEYPOT_FIELD;
    $timestamp = \App\Http\Requests\Site\PublicFormRequest::TIMESTAMP_FIELD;
@endphp

<div class="honeypot" aria-hidden="true">
    <label for="{{ $honeypot }}">Ne remplissez pas ce champ</label>
    <input
        type="text"
        id="{{ $honeypot }}"
        name="{{ $honeypot }}"
        value=""
        tabindex="-1"
        autocomplete="off"
    >
</div>

<input type="hidden" name="{{ $timestamp }}" value="{{ time() }}">
