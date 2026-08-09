{{--
    Groupe de boutons radio présenté sous forme de cartes cliquables.

    Le <fieldset> et sa <legend> font annoncer la question entière par les
    lecteurs d'écran avant les choix, ce qu'un simple label ne permet pas.
--}}
@props([
    'name',
    'legend',
    'options' => [],
    'descriptions' => [],
    'value' => null,
    'required' => false,
    'help' => null,
])

@php
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $hasError = $errors->has($errorKey);
    $current = old($errorKey, $value);
    $baseId = 'groupe-'.str_replace(['[', ']', '.', '_'], '-', $name);
@endphp

<fieldset class="field {{ $hasError ? 'field--invalid' : '' }}" @if ($help) aria-describedby="{{ $baseId }}-aide" @endif>
    <legend class="field__label">
        {{ $legend }}
        @if ($required)
            <span class="required-mark" aria-hidden="true">*</span>
        @endif
    </legend>

    @if ($help)
        <span class="field__hint" id="{{ $baseId }}-aide">{{ $help }}</span>
    @endif

    <div class="choice-list">
        @foreach ($options as $optionValue => $optionLabel)
            @php $optionId = $baseId.'-'.\Illuminate\Support\Str::slug((string) $optionValue); @endphp

            <label class="choice" for="{{ $optionId }}">
                <input
                    class="choice__input"
                    type="radio"
                    id="{{ $optionId }}"
                    name="{{ $name }}"
                    value="{{ $optionValue }}"
                    @checked((string) $current === (string) $optionValue)
                    @if ($required) required aria-required="true" @endif
                    @if (isset($descriptions[$optionValue])) aria-describedby="{{ $optionId }}-description" @endif
                >

                <span class="choice__text">
                    <span class="choice__label">{{ $optionLabel }}</span>

                    @if (isset($descriptions[$optionValue]))
                        <span class="choice__description" id="{{ $optionId }}-description">
                            {{ $descriptions[$optionValue] }}
                        </span>
                    @endif
                </span>
            </label>
        @endforeach
    </div>

    @error($errorKey)
        <p class="field__error">{{ $message }}</p>
    @enderror
</fieldset>
