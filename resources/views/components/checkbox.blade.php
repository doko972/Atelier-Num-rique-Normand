{{--
    Case à cocher avec sa zone de clic élargie.

    Toute l'étiquette est cliquable : viser une case de 15 pixels est
    difficile pour beaucoup de personnes.
--}}
@props([
    'name',
    'label',
    'value' => '1',
    'checked' => false,
    'description' => null,
    'required' => false,
])

@php
    $id = 'case-'.str_replace(['[', ']', '.', '_'], '-', $name).'-'.\Illuminate\Support\Str::slug((string) $value);
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $isChecked = old($errorKey) !== null
        ? in_array((string) $value, (array) old($errorKey), strict: true) || old($errorKey) === (string) $value
        : $checked;
@endphp

<label class="choice" for="{{ $id }}">
    <input
        class="choice__input"
        type="checkbox"
        id="{{ $id }}"
        name="{{ $name }}"
        value="{{ $value }}"
        @checked($isChecked)
        @if ($required) required aria-required="true" @endif
        @if ($description) aria-describedby="{{ $id }}-description" @endif
        {{ $attributes }}
    >

    <span class="choice__text">
        <span class="choice__label">
            {{ $label }}
            @if ($required)
                <span class="required-mark" aria-hidden="true">*</span>
            @endif
        </span>

        @if ($description)
            <span class="choice__description" id="{{ $id }}-description">{{ $description }}</span>
        @endif
    </span>
</label>

@error($errorKey)
    <p class="field__error">{{ $message }}</p>
@enderror
