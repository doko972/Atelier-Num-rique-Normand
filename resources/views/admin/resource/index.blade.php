{{--
    Liste générique d'une ressource éditoriale.

    Les colonnes sont décrites une seule fois, côté contrôleur (App\Admin\Field),
    ce qui évite quatorze tableaux presque identiques à maintenir.
--}}
@extends('layouts.admin')

@section('title', $labels['title'])

@section('content')
    <div class="admin-page-header">
        <div>
            <h1>{{ $labels['title'] }}</h1>

            @if (! empty($labels['intro']))
                <p>{{ $labels['intro'] }}</p>
            @endif
        </div>

        @can('create', $modelClass)
            <a class="btn btn--primary" href="{{ route("admin.{$routeKey}.create") }}">
                {{ __('admin.common.create') }}
            </a>
        @endcan
    </div>

    <div class="admin-panel">
        <form method="GET" action="{{ route("admin.{$routeKey}.index") }}" class="filters" role="search">
            <x-field
                name="recherche"
                type="search"
                :label="__('admin.common.search')"
                :value="$filters['recherche'] ?? null"
                :placeholder="__('admin.common.search_placeholder')"
            />

            @if ($softDeletes)
                <x-field
                    name="corbeille"
                    type="select"
                    label="Affichage"
                    :options="['0' => __('admin.common.active_items'), '1' => __('admin.common.trash')]"
                    :value="(string) ($filters['corbeille'] ?? '0')"
                />
            @endif

            <div class="field">
                <button type="submit" class="btn btn--primary">{{ __('admin.common.filter') }}</button>
            </div>

            <div class="field">
                <a class="btn btn--ghost" href="{{ route("admin.{$routeKey}.index") }}">
                    {{ __('admin.common.reset') }}
                </a>
            </div>
        </form>

        @if ($records->isEmpty())
            <p class="table__empty">{{ __('admin.common.empty') }}</p>
        @else
            <p class="text-small text-muted">{{ __('admin.common.results', ['count' => $records->total()]) }}</p>

            <div class="table-wrap" style="margin-top: 0.75rem">
                <table class="table table--stacked">
                    <caption class="visually-hidden">{{ $labels['title'] }}</caption>

                    <thead>
                        <tr>
                            @foreach ($fields as $field)
                                <th scope="col">
                                    @php
                                        $isSorted = $sort === $field->name;
                                        $nextDirection = $isSorted && $direction === 'asc' ? 'desc' : 'asc';
                                    @endphp

                                    <a
                                        class="table__sort"
                                        href="{{ route("admin.{$routeKey}.index", array_merge(request()->query(), ['tri' => $field->name, 'sens' => $nextDirection])) }}"
                                        @if ($isSorted) aria-sort="{{ $direction === 'asc' ? 'ascending' : 'descending' }}" @endif
                                    >
                                        {{ $field->label }}
                                        <span class="visually-hidden">
                                            {{ __('admin.common.sort_by', ['column' => $field->label]) }}
                                        </span>
                                    </a>
                                </th>
                            @endforeach

                            <th scope="col">{{ __('admin.common.actions') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($records as $record)
                            <tr>
                                @foreach ($fields as $field)
                                    <td data-label="{{ $field->label }}">
                                        @php $value = $record->{$field->name}; @endphp

                                        @if ($field->type === 'boolean')
                                            {{ $value ? __('admin.common.yes') : __('admin.common.no') }}
                                        @elseif ($value instanceof \BackedEnum)
                                            <x-badge :variant="method_exists($value, 'badgeVariant') ? $value->badgeVariant() : 'neutral'">
                                                {{ method_exists($value, 'label') ? $value->label() : $value->value }}
                                            </x-badge>
                                        @elseif ($field->listFormat === 'money')
                                            {{ $value === null ? '—' : \Illuminate\Support\Number::currency($value / 100, 'EUR', 'fr') }}
                                        @elseif ($value instanceof \DateTimeInterface)
                                            {{ \Carbon\CarbonImmutable::parse($value)->format('d/m/Y') }}
                                        @elseif ($field->name === 'url')
                                            <a href="{{ $value }}" rel="noopener noreferrer" target="_blank">{{ parse_url((string) $value, PHP_URL_HOST) }}</a>
                                        @else
                                            {{ \Illuminate\Support\Str::limit((string) $value, 90) ?: '—' }}
                                        @endif
                                    </td>
                                @endforeach

                                <td data-label="{{ __('admin.common.actions') }}">
                                    <div class="table__actions">
                                        @if ($softDeletes && $record->trashed())
                                            @can('restore', $record)
                                                <form method="POST" action="{{ route("admin.{$routeKey}.restore", $record->getKey()) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn--outline btn--sm">
                                                        {{ __('admin.common.restore') }}
                                                        <span class="visually-hidden">: {{ $record->auditLabel() }}</span>
                                                    </button>
                                                </form>
                                            @endcan
                                        @else
                                            @can('update', $record)
                                                <a class="btn btn--outline btn--sm" href="{{ route("admin.{$routeKey}.edit", $record) }}">
                                                    {{ __('admin.common.edit') }}
                                                    <span class="visually-hidden">: {{ $record->auditLabel() }}</span>
                                                </a>
                                            @endcan

                                            @if ($routeKey === 'guides')
                                                <a class="btn btn--ghost btn--sm" href="{{ route('admin.guides.steps.index', $record) }}">
                                                    {{ __('admin.guides.manage_steps') }}
                                                    <span class="visually-hidden">: {{ $record->title }}</span>
                                                </a>
                                            @endif

                                            @can('delete', $record)
                                                <form
                                                    method="POST"
                                                    action="{{ route("admin.{$routeKey}.destroy", $record) }}"
                                                    data-confirm="{{ __('admin.common.delete_confirm') }}"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn--danger btn--sm">
                                                        {{ __('admin.common.delete') }}
                                                        <span class="visually-hidden">: {{ $record->auditLabel() }}</span>
                                                    </button>
                                                </form>
                                            @endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $records->links() }}
        @endif
    </div>
@endsection
