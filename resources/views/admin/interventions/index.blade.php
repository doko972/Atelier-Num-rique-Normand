@extends('layouts.admin')

@section('title', __('admin.interventions.title'))

@section('content')
    <div class="admin-page-header">
        <div>
            <h1>{{ __('admin.interventions.title') }}</h1>
            <p>{{ __('admin.interventions.intro') }}</p>
        </div>

        <div class="btn-group">
            <a class="btn btn--outline" href="{{ route('admin.interventions.report', ['annee' => $year]) }}">
                {{ __('admin.interventions.report_link') }}
            </a>

            @can('create', \App\Models\Intervention::class)
                <a class="btn btn--primary" href="{{ route('admin.interventions.create') }}">
                    {{ __('admin.interventions.create') }}
                </a>
            @endcan
        </div>
    </div>

    @include('admin.interventions._summary', ['summary' => $summary])

    <div class="admin-panel">
        <form method="GET" action="{{ route('admin.interventions.index') }}" class="filters" role="search">
            <x-field
                name="annee"
                type="select"
                :label="__('admin.interventions.filters.year')"
                :options="collect($years)->mapWithKeys(fn (int $y) => [$y => (string) $y])->all()"
                :value="$year"
            />

            <x-field
                name="nature"
                type="select"
                :label="__('admin.interventions.filters.nature')"
                :options="[
                    'sap' => __('admin.interventions.filters.sap'),
                    'hors_sap' => __('admin.interventions.filters.other'),
                ]"
                :value="$filters['nature'] ?? null"
                :empty-option="__('admin.interventions.filters.all')"
            />

            <x-field
                name="recherche"
                type="search"
                :label="__('admin.common.search')"
                :value="$filters['recherche'] ?? null"
                placeholder="Client, référence, n° de facture…"
            />

            <x-field
                name="corbeille"
                type="select"
                label="Affichage"
                :options="['0' => __('admin.common.active_items'), '1' => __('admin.common.trash')]"
                :value="(string) ($filters['corbeille'] ?? '0')"
            />

            <div class="field">
                <button type="submit" class="btn btn--primary">{{ __('admin.common.filter') }}</button>
            </div>

            <div class="field">
                <a class="btn btn--ghost" href="{{ route('admin.interventions.index') }}">{{ __('admin.common.reset') }}</a>
            </div>
        </form>

        @if ($interventions->isEmpty())
            <p class="table__empty">{{ __('admin.interventions.empty') }}</p>
        @else
            <div class="admin-page-header" style="margin-top: 0.75rem">
                <p class="text-small text-muted">{{ __('admin.common.results', ['count' => $interventions->total()]) }}</p>

                @can('export', \App\Models\Intervention::class)
                    <a class="btn btn--outline btn--sm" href="{{ route('admin.interventions.export', request()->query()) }}">
                        {{ __('admin.interventions.export') }}
                    </a>
                @endcan
            </div>

            <div class="table-wrap" style="margin-top: 0.75rem">
                <table class="table table--stacked">
                    <caption class="visually-hidden">{{ __('admin.interventions.title') }} {{ $year }}</caption>

                    <thead>
                        <tr>
                            <th scope="col">{{ __('admin.interventions.columns.date') }}</th>
                            <th scope="col">{{ __('admin.interventions.columns.reference') }}</th>
                            <th scope="col">{{ __('admin.interventions.columns.client') }}</th>
                            <th scope="col">{{ __('admin.interventions.columns.activity') }}</th>
                            <th scope="col">{{ __('admin.interventions.columns.duration') }}</th>
                            <th scope="col">{{ __('admin.interventions.columns.amount') }}</th>
                            <th scope="col">{{ __('admin.common.actions') }}</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($interventions as $intervention)
                            <tr>
                                <td data-label="{{ __('admin.interventions.columns.date') }}">
                                    {{ $intervention->performed_on->format('d/m/Y') }}
                                </td>
                                <td data-label="{{ __('admin.interventions.columns.reference') }}">
                                    {{ $intervention->reference }}
                                    @if ($intervention->invoice_number)
                                        <br><span class="text-small text-muted">Facture {{ $intervention->invoice_number }}</span>
                                    @endif
                                </td>
                                <td data-label="{{ __('admin.interventions.columns.client') }}">
                                    {{ $intervention->client_name }}
                                    @if ($intervention->appointment && ! $intervention->appointment->trashed())
                                        <br>
                                        <a class="text-small" href="{{ route('admin.appointments.show', $intervention->appointment) }}">
                                            {{ $intervention->appointment->reference }}
                                        </a>
                                    @endif
                                </td>
                                <td data-label="{{ __('admin.interventions.columns.activity') }}">
                                    <x-badge :variant="$intervention->activity->badgeVariant()">
                                        {{ $intervention->isSap() ? 'SAP' : 'Hors SAP' }}
                                    </x-badge>
                                    {{ $intervention->activity->label() }}
                                </td>
                                <td data-label="{{ __('admin.interventions.columns.duration') }}">
                                    {{ \App\Support\FrenchFormat::duration($intervention->duration_minutes) }}
                                </td>
                                <td data-label="{{ __('admin.interventions.columns.amount') }}">
                                    {{ $intervention->formattedAmount() }}
                                </td>
                                <td data-label="{{ __('admin.common.actions') }}">
                                    <div class="table__actions">
                                        @if ($intervention->trashed())
                                            @can('restore', $intervention)
                                                <form method="POST" action="{{ route('admin.interventions.restore', $intervention->id) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn--outline btn--sm">
                                                        {{ __('admin.common.restore') }}
                                                        <span class="visually-hidden">: {{ $intervention->reference }}</span>
                                                    </button>
                                                </form>
                                            @endcan
                                        @else
                                            @can('update', $intervention)
                                                <a class="btn btn--outline btn--sm" href="{{ route('admin.interventions.edit', $intervention) }}">
                                                    {{ __('admin.common.edit') }}
                                                    <span class="visually-hidden">: {{ $intervention->reference }}</span>
                                                </a>
                                            @endcan

                                            @can('delete', $intervention)
                                                <form
                                                    method="POST"
                                                    action="{{ route('admin.interventions.destroy', $intervention) }}"
                                                    data-confirm="{{ __('admin.interventions.delete_confirm') }}"
                                                >
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn--danger btn--sm">
                                                        {{ __('admin.common.delete') }}
                                                        <span class="visually-hidden">: {{ $intervention->reference }}</span>
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

            {{ $interventions->links() }}
        @endif
    </div>
@endsection
