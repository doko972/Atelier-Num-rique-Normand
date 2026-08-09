{{--
    Formulaire générique de création et de modification.

    Chaque champ est rendu à partir de sa description (App\Admin\Field), ce qui
    garantit que le libellé, l'aide et la règle de validation restent alignés.
--}}
@extends('layouts.admin')

@section('title', ($isNew ? __('admin.common.create') : __('admin.common.edit')).' — '.$labels['title'])

@section('content')
    <div class="admin-page-header">
        <div>
            <h1>
                {{ $isNew ? __('admin.common.create') : __('admin.common.edit') }}
                {{ $labels['singular'] }}
            </h1>

            @if (! empty($labels['intro']))
                <p>{{ $labels['intro'] }}</p>
            @endif
        </div>

        <a class="btn btn--ghost" href="{{ route("admin.{$routeKey}.index") }}">
            {{ __('admin.common.back') }}
        </a>
    </div>

    <div class="admin-panel">
        <x-form-errors />

        <p class="form__required-note">{{ __('admin.common.required_fields') }}</p>

        <form
            method="POST"
            action="{{ $isNew ? route("admin.{$routeKey}.store") : route("admin.{$routeKey}.update", $record) }}"
            class="form"
        >
            @csrf
            @unless ($isNew)
                @method('PUT')
            @endunless

            @foreach ($fields as $field)
                @php
                    $value = $record->{$field->name} ?? $field->default;

                    if ($value instanceof \BackedEnum) {
                        $value = $value->value;
                    } elseif ($value instanceof \DateTimeInterface) {
                        $value = \Carbon\CarbonImmutable::parse($value)->format('Y-m-d');
                    } elseif (is_array($value)) {
                        $value = implode("\n", $value);
                    }
                @endphp

                @if ($field->type === 'boolean')
                    <div class="field">
                        <x-checkbox
                            :name="$field->name"
                            :label="$field->label"
                            :description="$field->help"
                            :checked="(bool) ($record->{$field->name} ?? $field->default)"
                        />
                    </div>
                @elseif ($field->type === 'select')
                    <x-field
                        :name="$field->name"
                        type="select"
                        :label="$field->label"
                        :options="$field->options"
                        :value="$value"
                        :help="$field->help"
                        :required="$field->isRequired()"
                        :empty-option="$field->isRequired() ? null : __('admin.common.none')"
                    />
                @elseif (in_array($field->type, ['textarea', 'richtext', 'lines'], true))
                    <x-field
                        :name="$field->name"
                        type="textarea"
                        :label="$field->label"
                        :value="$value"
                        :help="$field->help"
                        :required="$field->isRequired()"
                        :rows="$field->rows ?? 5"
                    />
                @else
                    <x-field
                        :name="$field->name"
                        :type="$field->type"
                        :label="$field->label"
                        :value="$value"
                        :help="$field->help"
                        :required="$field->isRequired()"
                    />
                @endif
            @endforeach

            <div class="form__actions">
                <button type="submit" class="btn btn--primary btn--lg">
                    {{ __('admin.common.save') }}
                </button>

                <a class="btn btn--ghost" href="{{ route("admin.{$routeKey}.index") }}">
                    {{ __('admin.common.cancel') }}
                </a>
            </div>
        </form>
    </div>
@endsection
