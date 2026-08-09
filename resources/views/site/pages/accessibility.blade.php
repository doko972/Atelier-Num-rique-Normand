@extends('layouts.site')

@section('title', $page->meta_title ?: $page->title)
@section('meta_description', $page->meta_description ?: $page->summary)

@section('breadcrumb')
    <x-breadcrumb :items="[$page->title => null]" />
@endsection

@section('content')
    <div class="page-header">
        <div class="container container--narrow">
            <h1>{{ $page->title }}</h1>

            @if ($page->summary)
                <p>{{ $page->summary }}</p>
            @endif
        </div>
    </div>

    <section class="section">
        <div class="container container--narrow">
            <h2>{{ __('site.accessibility.report_title') }}</h2>

            @if ($report)
                <div class="callout" style="margin-top: 1rem">
                    <p>
                        <strong>{{ $report->levelLabel() }}</strong>
                        au référentiel {{ $report->referential }}.
                    </p>

                    @if ($report->compliance_rate !== null)
                        <p>{{ __('site.accessibility.rate', ['rate' => $report->compliance_rate]) }}</p>
                    @endif

                    <p class="text-small text-muted">
                        {{ __('site.accessibility.audited_on', [
                            'date' => $report->audited_on->locale('fr')->isoFormat('D MMMM YYYY'),
                        ]) }}
                        @if ($report->auditor)
                            — {{ $report->auditor }}
                        @endif
                    </p>
                </div>

                @if ($report->summary)
                    <div class="prose" style="margin-top: 1.5rem">{!! nl2br(e($report->summary)) !!}</div>
                @endif

                @if (filled($report->non_conformities))
                    <h3 style="margin-top: 2rem">{{ __('site.accessibility.non_conformities') }}</h3>
                    <ul role="list" style="margin-top: 0.75rem">
                        @foreach ($report->non_conformities as $item)
                            <li>{{ is_array($item) ? ($item['label'] ?? json_encode($item)) : $item }}</li>
                        @endforeach
                    </ul>
                @endif

                @if ($report->improvement_plan)
                    <h3 style="margin-top: 2rem">{{ __('site.accessibility.improvement_plan') }}</h3>
                    <div class="prose">{!! nl2br(e($report->improvement_plan)) !!}</div>
                @endif
            @else
                <x-alert variant="warning">
                    <p>{{ __('site.accessibility.no_report') }}</p>
                </x-alert>
            @endif

            <div class="prose" style="margin-top: 2rem">{!! nl2br(e($page->body)) !!}</div>

            <x-phone-cta />
        </div>
    </section>
@endsection
