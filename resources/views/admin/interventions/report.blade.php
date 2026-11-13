@extends('layouts.admin')

@php
    use App\Enums\InterventionActivity;
    use App\Support\FrenchFormat;
@endphp

@section('title', __('admin.interventions.report.title', ['year' => $year]))

@section('content')
    <div class="admin-page-header">
        <div>
            <h1>{{ __('admin.interventions.report.title', ['year' => $year]) }}</h1>
            <p>{{ __('admin.interventions.report.intro') }}</p>
        </div>

        <div class="btn-group">
            <a class="btn btn--outline" href="{{ route('admin.interventions.index', ['annee' => $year]) }}">
                {{ __('admin.interventions.report.list_link') }}
            </a>

            @can('export', \App\Models\Intervention::class)
                <a class="btn btn--primary" href="{{ route('admin.interventions.report.export', ['annee' => $year]) }}">
                    {{ __('admin.interventions.report.export') }}
                </a>
            @endcan
        </div>
    </div>

    <div class="admin-panel">
        <form method="GET" action="{{ route('admin.interventions.report') }}" class="filters">
            <x-field
                name="annee"
                type="select"
                :label="__('admin.interventions.filters.year')"
                :options="collect($years)->mapWithKeys(fn (int $y) => [$y => (string) $y])->all()"
                :value="$year"
            />

            <div class="field">
                <button type="submit" class="btn btn--primary">{{ __('admin.common.filter') }}</button>
            </div>
        </form>
    </div>

    @include('admin.interventions._summary', ['summary' => $summary])

    <section class="admin-panel" aria-labelledby="trimestres">
        <h2 class="admin-panel__title" id="trimestres">{{ __('admin.interventions.report.quarters_title') }}</h2>

        <div class="table-wrap" style="margin-top: 0.75rem">
            <table class="table">
                <caption class="visually-hidden">{{ __('admin.interventions.report.title', ['year' => $year]) }}</caption>

                <thead>
                    <tr>
                        <td></td>
                        <th scope="colgroup" colspan="3">{{ __('admin.interventions.groups.it_assistance') }}</th>
                        <th scope="colgroup" colspan="3">{{ __('admin.interventions.groups.administrative_assistance') }}</th>
                        <th scope="colgroup" colspan="3">{{ __('admin.interventions.report.total_sap') }}</th>
                        <th scope="colgroup" colspan="2">{{ __('admin.interventions.report.other') }}</th>
                    </tr>
                    <tr>
                        <th scope="col">Trimestre</th>
                        @foreach (range(1, 3) as $_)
                            <th scope="col">{{ __('admin.interventions.report.hours') }}</th>
                            <th scope="col">{{ __('admin.interventions.report.revenue') }}</th>
                            <th scope="col">{{ __('admin.interventions.report.clients') }}</th>
                        @endforeach
                        <th scope="col">{{ __('admin.interventions.report.hours') }}</th>
                        <th scope="col">{{ __('admin.interventions.report.revenue') }}</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($quarters as $number => $quarter)
                        <tr>
                            <th scope="row">{{ __('admin.interventions.report.quarter', ['number' => $number]) }}</th>

                            @foreach ([InterventionActivity::GROUP_IT, InterventionActivity::GROUP_ADMINISTRATIVE] as $group)
                                <td>{{ FrenchFormat::duration($quarter['groups'][$group]['minutes']) }}</td>
                                <td>{{ FrenchFormat::money($quarter['groups'][$group]['cents']) }}</td>
                                <td>{{ $quarter['groups'][$group]['clients'] }}</td>
                            @endforeach

                            <td><strong>{{ FrenchFormat::duration($quarter['sap_minutes']) }}</strong></td>
                            <td><strong>{{ FrenchFormat::money($quarter['sap_cents']) }}</strong></td>
                            <td><strong>{{ $quarter['sap_clients'] }}</strong></td>

                            <td>{{ FrenchFormat::duration($quarter['other_minutes']) }}</td>
                            <td>{{ FrenchFormat::money($quarter['other_cents']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <p class="text-small text-muted" style="margin-top: 0.75rem">{{ __('admin.interventions.report.clients_help') }}</p>
    </section>
@endsection
