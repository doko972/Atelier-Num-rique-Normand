{{--
    Champ de formulaire accessible.

    Un champ = un label visible, une aide facultative, et le message d'erreur
    juste en dessous. Le lien entre les trois est déclaré par aria-describedby
    pour que les lecteurs d'écran annoncent l'ensemble (codex §6).
--}}
@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'help' => null,
    'required' => false,
    'options' => [],
    'placeholder' => null,
    'rows' => 5,
    'autocomplete' => null,
    'min' => null,
    'max' => null,
    'step' => null,
    'maxlength' => null,
    'emptyOption' => null,
])

@php
    $id = $attributes->get('id', 'champ-'.str_replace(['[', ']', '.', '_'], '-', $name));
    $errorKey = str_replace(['[', ']'], ['.', ''], $name);
    $hasError = $errors->has($errorKey);

    $describedBy = collect([
        $help ? $id.'-aide' : null,
        $hasError ? $id.'-erreur' : null,
    ])->filter()->implode(' ');

    $current = old($errorKey, $value);
@endphp

<div class="field {{ $hasError ? 'field--invalid' : '' }}">
    <label class="field__label" for="{{ $id }}">
        {{ $label }}
        @if ($required)
            <span class="required-mark" aria-hidden="true">*</span>
        @else
            <span class="text-muted text-small">({{ __('site.common.optional') }})</span>
        @endif
    </label>

    @if ($help)
        <span class="field__hint" id="{{ $id }}-aide">{{ $help }}</span>
    @endif

    @if ($type === 'textarea')
        <textarea
            class="field__control"
            id="{{ $id }}"
            name="{{ $name }}"
            rows="{{ $rows }}"
            @if ($required) required aria-required="true" @endif
            @if ($maxlength) maxlength="{{ $maxlength }}" @endif
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($hasError) aria-invalid="true" @endif
            {{ $attributes->except(['id', 'class']) }}
        >{{ $current }}</textarea>
    @elseif ($type === 'select')
        <select
            class="field__control"
            id="{{ $id }}"
            name="{{ $name }}"
            @if ($required) required aria-required="true" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($hasError) aria-invalid="true" @endif
            {{ $attributes->except(['id', 'class']) }}
        >
            @if ($emptyOption !== null)
                <option value="">{{ $emptyOption }}</option>
            @endif

            @foreach ($options as $optionValue => $optionLabel)
                <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>
                    {{ $optionLabel }}
                </option>
            @endforeach
        </select>
    @else
        <input
            class="field__control"
            type="{{ $type }}"
            id="{{ $id }}"
            name="{{ $name }}"
            value="{{ $current }}"
            @if ($required) required aria-required="true" @endif
            @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
            @if ($placeholder) placeholder="{{ $placeholder }}" @endif
            @if ($min !== null) min="{{ $min }}" @endif
            @if ($max !== null) max="{{ $max }}" @endif
            @if ($step !== null) step="{{ $step }}" @endif
            @if ($maxlength) maxlength="{{ $maxlength }}" @endif
            @if ($describedBy) aria-describedby="{{ $describedBy }}" @endif
            @if ($hasError) aria-invalid="true" @endif
            {{ $attributes->except(['id', 'class']) }}
        >
    @endif

    @error($errorKey)
        <p class="field__error" id="{{ $id }}-erreur">{{ $message }}</p>
    @enderror
</div>
