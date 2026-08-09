@extends('layouts.admin')

@section('title', __('admin.audit.title'))

@section('content')
    <div class="admin-page-header">
        <div>
            <h1>{{ __('admin.audit.title') }}</h1>
            <p>{{ __('admin.audit.intro') }}</p>
        </div>
    </div>

    <div class="admin-panel">
        <form method="GET" action="{{ route('admin.audit-logs.index') }}" class="filters" role="search">
            <x-field
                name="action"
                type="select"
                :label="__('admin.audit.what')"
                :options="$actions->mapWithKeys(fn ($action) => [$action => __('admin.audit.actions.'.$action)])->all()"
                :value="$filters['action'] ?? null"
                :empty-option="__('admin.common.all')"
            />

            <x-field
                name="utilisateur"
                type="select"
                :label="__('admin.audit.who')"
                :options="$users->pluck('name', 'id')->all()"
                :value="$filters['utilisateur'] ?? null"
                :empty-option="__('admin.common.all')"
            />

            <x-field
                name="canal"
                type="select"
                :label="__('admin.audit.channel')"
                :options="$channels->mapWithKeys(fn ($channel) => [$channel => $channel])->all()"
                :value="$filters['canal'] ?? null"
                :empty-option="__('admin.common.all')"
            />

            <div class="field">
                <button type="submit" class="btn btn--primary">{{ __('admin.common.filter') }}</button>
            </div>
        </form>

        @if ($logs->isEmpty())
            <p class="table__empty">{{ __('admin.audit.empty') }}</p>
        @else
            <div class="table-wrap">
                <table class="table table--stacked">
                    <caption class="visually-hidden">{{ __('admin.audit.title') }}</caption>

                    <thead>
                        <tr>
                            <th scope="col">{{ __('admin.audit.when') }}</th>
                            <th scope="col">{{ __('admin.audit.who') }}</th>
                            <th scope="col">{{ __('admin.audit.what') }}</th>
                            <th scope="col">{{ __('admin.audit.subject') }}</th>
                            <th scope="col">{{ __('admin.audit.channel') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td data-label="{{ __('admin.audit.when') }}">
                                    <time datetime="{{ $log->created_at?->toIso8601String() }}">
                                        {{ $log->created_at?->format('d/m/Y à H\\hi\\ms') }}
                                    </time>
                                </td>

                                <td data-label="{{ __('admin.audit.who') }}">{{ $log->authorName() }}</td>

                                <td data-label="{{ __('admin.audit.what') }}">
                                    {{ __("admin.audit.actions.{$log->action}") }}
                                </td>

                                <td data-label="{{ __('admin.audit.subject') }}">
                                    {{ $log->subject_label ?? '—' }}
                                    @if ($log->auditable_type)
                                        <br>
                                        <span class="text-small text-muted">{{ class_basename($log->auditable_type) }}</span>
                                    @endif
                                </td>

                                <td data-label="{{ __('admin.audit.channel') }}">{{ $log->channel }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $logs->links() }}
        @endif
    </div>
@endsection
