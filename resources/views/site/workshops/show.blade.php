@extends('layouts.site')

@section('title', $workshop->meta_title ?: $workshop->title)
@section('meta_description', $workshop->meta_description ?: \Illuminate\Support\Str::limit(strip_tags($workshop->description), 150))

@section('breadcrumb')
    <x-breadcrumb :items="[
        __('site.workshops.title') => route('workshops.index'),
        $workshop->title => null,
    ]" />
@endsection

@push('head')
    {{-- Schema.org Event : les ateliers peuvent apparaître dans l'agenda de Google --}}
    <x-structured-data :data="\App\Support\StructuredData::event($workshop)" />
@endpush

@section('content')
    <div class="page-header">
        <div class="container">
            <h1>{{ $workshop->title }}</h1>
            <p>{{ $workshop->date->locale('fr')->isoFormat('dddd D MMMM YYYY') }},
                {{ $workshop->startsAt()->format('H\\hi') }} – {{ $workshop->endsAt()->format('H\\hi') }}</p>
        </div>
    </div>

    <section class="section">
        <div class="container">
            @if ($workshop->status === \App\Enums\WorkshopStatus::Cancelled)
                <x-alert variant="danger" :title="__('site.workshops.cancelled')">
                    @if ($workshop->cancellation_reason)
                        <p>{{ $workshop->cancellation_reason }}</p>
                    @endif
                    <p>{{ __('site.workshops.phone_alternative') }}</p>
                </x-alert>
            @endif

            <div class="split--aside split">
                <div class="stack">
                    <div class="prose">{!! nl2br(e($workshop->description)) !!}</div>

                    @if (filled($workshop->objectives))
                        <section aria-labelledby="objectifs">
                            <h2 id="objectifs">{{ __('site.workshops.objectives') }}</h2>
                            <ul class="check-list check-list--yes" role="list">
                                @foreach ($workshop->objectives as $objective)
                                    <li>{{ $objective }}</li>
                                @endforeach
                            </ul>
                        </section>
                    @endif

                    @if ($workshop->prerequisites)
                        <section aria-labelledby="prerequis">
                            <h2 id="prerequis">{{ __('site.workshops.prerequisites') }}</h2>
                            <p>{{ $workshop->prerequisites }}</p>
                        </section>
                    @endif

                    @if ($workshop->files->isNotEmpty())
                        <section aria-labelledby="documents">
                            <h2 id="documents">{{ __('site.workshops.documents') }}</h2>
                            <ul role="list">
                                @foreach ($workshop->files as $file)
                                    <li>
                                        <a href="{{ $file->url() }}">
                                            {{ $file->title }}
                                            <span class="text-muted">({{ $file->extension() }}, {{ $file->humanSize() }})</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </section>
                    @endif
                </div>

                <aside class="stack">
                    <div class="callout">
                        <h2 class="callout__title">{{ __('site.workshops.when') }}</h2>

                        <p>
                            {{ $workshop->date->locale('fr')->isoFormat('dddd D MMMM YYYY') }}<br>
                            {{ $workshop->startsAt()->format('H\\hi') }} – {{ $workshop->endsAt()->format('H\\hi') }}
                        </p>

                        @if ($workshop->registration_deadline)
                            <p class="text-small">
                                {{ __('site.workshops.deadline', [
                                    'date' => $workshop->registration_deadline->locale('fr')->isoFormat('D MMMM YYYY'),
                                ]) }}
                            </p>
                        @endif
                    </div>

                    @if ($workshop->location)
                        <div class="callout">
                            <h2 class="callout__title">{{ __('site.workshops.where') }}</h2>

                            <p>
                                <strong>{{ $workshop->location->name }}</strong><br>
                                {{ $workshop->location->fullAddress() }}
                            </p>

                            @if ($workshop->location->access_notes)
                                <p class="text-small">{{ $workshop->location->access_notes }}</p>
                            @endif

                            <p>
                                @if ($workshop->location->is_accessible)
                                    <x-badge variant="success">{{ __('site.workshops.accessible') }}</x-badge>
                                @else
                                    <x-badge variant="warning">{{ __('site.workshops.not_accessible') }}</x-badge>
                                @endif
                            </p>

                            <p>
                                <a href="{{ $workshop->location->mapUrl() }}" rel="noopener noreferrer" target="_blank">
                                    {{ __('site.contact.open_map') }}
                                    <span class="visually-hidden">({{ __('site.common.new_window') }})</span>
                                </a>
                            </p>
                        </div>
                    @endif

                    <div class="callout">
                        <h2 class="callout__title">{{ __('site.workshops.seats_label') }}</h2>

                        @php $remaining = $workshop->remainingSeats(); @endphp

                        <p class="{{ $remaining === 0 ? 'seats seats--none' : ($remaining <= config('site.workshops.low_seats_threshold') ? 'seats seats--low' : 'seats') }}">
                            {{ trans_choice('site.workshops.seats_remaining', $remaining, ['count' => $remaining]) }}
                        </p>

                        <ul class="card__meta" style="flex-direction: column; align-items: flex-start">
                            <li>{{ __('site.workshops.level') }} : {{ $workshop->level->label() }}</li>
                            <li>{{ $workshop->formattedPrice() }}</li>
                            @if ($workshop->instructor_name)
                                <li>{{ __('site.workshops.instructor') }} : {{ $workshop->instructor_name }}</li>
                            @endif
                        </ul>

                        <p>
                            @if ($workshop->equipment_provided)
                                {{ __('site.workshops.equipment_provided') }}
                            @endif
                            @if ($workshop->own_device_allowed)
                                {{ __('site.workshops.bring_device') }}
                            @endif
                        </p>

                        @if ($workshop->registrationsOpen())
                            <a class="btn btn--accent btn--lg btn--block" href="{{ route('workshops.register', $workshop) }}">
                                {{ __('site.workshops.register') }}
                            </a>
                        @elseif ($workshop->waitingListOpen())
                            <p>{{ __('site.workshops.waiting_list_open') }}</p>
                            <a class="btn btn--outline btn--lg btn--block" href="{{ route('workshops.register', $workshop) }}">
                                {{ __('site.workshops.register_waiting') }}
                            </a>
                        @elseif ($workshop->status !== \App\Enums\WorkshopStatus::Cancelled)
                            <p>{{ __('site.workshops.registrations_closed') }}</p>
                        @endif
                    </div>

                    <x-phone-cta />
                </aside>
            </div>

            @if ($related->isNotEmpty())
                <section aria-labelledby="autres-ateliers" style="margin-top: 3rem">
                    <h2 id="autres-ateliers">{{ __('site.workshops.related') }}</h2>

                    <div class="stack" style="margin-top: 1rem">
                        @foreach ($related as $other)
                            <x-workshop-card :workshop="$other" />
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    </section>
@endsection
