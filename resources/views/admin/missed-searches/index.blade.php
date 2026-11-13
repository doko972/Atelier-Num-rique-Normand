@extends('layouts.admin')

@section('title', __('admin.missed_searches.title'))

@section('content')
    <div class="admin-page-header">
        <div>
            <h1>{{ __('admin.missed_searches.title') }}</h1>
            <p>{{ __('admin.missed_searches.intro') }}</p>
        </div>

        <a class="btn btn--outline" href="{{ route('admin.search-synonyms.index') }}">
            {{ __('admin.nav.search_synonyms') }}
        </a>
    </div>

    <div class="admin-panel">
        @if ($searches->isEmpty())
            <p class="table__empty">{{ __('admin.missed_searches.empty') }}</p>
        @else
            <div class="table-wrap">
                <table class="table table--stacked">
                    <caption class="visually-hidden">{{ __('admin.missed_searches.title') }}</caption>

                    <thead>
                        <tr>
                            <th scope="col">{{ __('admin.missed_searches.query') }}</th>
                            <th scope="col">{{ __('admin.missed_searches.hits') }}</th>
                            <th scope="col">{{ __('admin.missed_searches.last') }}</th>
                            <th scope="col">{{ __('admin.common.actions') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($searches as $search)
                            <tr>
                                <td data-label="{{ __('admin.missed_searches.query') }}">
                                    <strong>{{ $search->query }}</strong>
                                </td>
                                <td data-label="{{ __('admin.missed_searches.hits') }}">{{ $search->hits }}</td>
                                <td data-label="{{ __('admin.missed_searches.last') }}">
                                    {{ $search->last_searched_at?->format('d/m/Y') }}
                                </td>
                                <td data-label="{{ __('admin.common.actions') }}">
                                    <div class="table__actions">
                                        <a class="btn btn--outline btn--sm" href="{{ route('admin.search-synonyms.create', ['mot' => $search->query]) }}">
                                            {{ __('admin.missed_searches.add_synonym') }}
                                            <span class="visually-hidden">: {{ $search->query }}</span>
                                        </a>

                                        <a class="btn btn--ghost btn--sm" href="{{ route('search', ['q' => $search->query]) }}" target="_blank" rel="noopener">
                                            {{ __('admin.missed_searches.retry') }}
                                            <span class="visually-hidden">: {{ $search->query }} ({{ __('site.common.new_window') }})</span>
                                        </a>

                                        <form method="POST" action="{{ route('admin.missed-searches.destroy', $search) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn--danger btn--sm">
                                                {{ __('admin.missed_searches.dismiss') }}
                                                <span class="visually-hidden">: {{ $search->query }}</span>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $searches->links() }}

            <form
                method="POST"
                action="{{ route('admin.missed-searches.clear') }}"
                data-confirm="{{ __('admin.missed_searches.clear_confirm') }}"
                style="margin-top: 1.5rem"
            >
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn--ghost">{{ __('admin.missed_searches.clear') }}</button>
            </form>
        @endif
    </div>
@endsection
