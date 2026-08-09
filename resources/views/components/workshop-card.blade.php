{{--
    Carte d'un atelier dans l'agenda.

    Le nombre de places restantes est écrit en toutes lettres ; la jauge n'est
    qu'un renfort visuel, marquée aria-hidden.
--}}
@props(['workshop'])

@php
    $remaining = $workshop->remainingSeats();
    $threshold = (int) config('site.workshops.low_seats_threshold');
    $cancelled = $workshop->status === \App\Enums\WorkshopStatus::Cancelled;

    $seatClass = match (true) {
        $remaining === 0 => 'seats seats--none',
        $remaining <= $threshold => 'seats seats--low',
        default => 'seats',
    };

    $cardModifier = match (true) {
        $cancelled => ' workshop-card--cancelled',
        $remaining === 0 => ' workshop-card--full',
        default => '',
    };

    $fillPercent = $workshop->capacity > 0
        ? min(100, (int) round($workshop->occupiedSeats() / $workshop->capacity * 100))
        : 100;
@endphp

<article {{ $attributes->merge(['class' => 'workshop-card'.$cardModifier]) }}>
    <p class="workshop-card__date">
        <span class="workshop-card__day">{{ $workshop->date->format('d') }}</span>
        <span class="workshop-card__month">{{ $workshop->date->locale('fr')->isoFormat('MMM') }}</span>
        <span class="workshop-card__year">{{ $workshop->date->format('Y') }}</span>
    </p>

    <div>
        <h3 class="workshop-card__title">
            <a href="{{ route('workshops.show', $workshop) }}">{{ $workshop->title }}</a>
        </h3>

        @if ($cancelled)
            <x-badge variant="danger" square>{{ __('site.workshops.cancelled') }}</x-badge>
        @endif

        <ul class="workshop-card__meta">
            <li>
                <x-icon name="horloge" width="18" height="18" />
                <span>{{ $workshop->startsAt()->format('H\\hi') }} – {{ $workshop->endsAt()->format('H\\hi') }}</span>
            </li>

            @if ($workshop->location)
                <li>
                    <x-icon name="lieu" width="18" height="18" />
                    <span>{{ $workshop->location->name }}@if ($workshop->location->city), {{ $workshop->location->city }}@endif</span>
                </li>
            @endif

            <li>
                <x-icon name="personnes" width="18" height="18" />
                <span>{{ $workshop->level->label() }}</span>
            </li>

            <li>{{ $workshop->formattedPrice() }}</li>
        </ul>
    </div>

    <div class="workshop-card__action">
        @unless ($cancelled)
            <p class="{{ $seatClass }}">
                {{ trans_choice('site.workshops.seats_remaining', $remaining, ['count' => $remaining]) }}
            </p>

            <span class="seats__gauge" aria-hidden="true">
                <span class="seats__fill" style="width: {{ $fillPercent }}%"></span>
            </span>

            @if ($workshop->registrationsOpen())
                <a class="btn btn--accent" href="{{ route('workshops.register', $workshop) }}">
                    {{ __('site.workshops.register') }}
                    <span class="visually-hidden">: {{ $workshop->title }}</span>
                </a>
            @elseif ($workshop->waitingListOpen())
                <a class="btn btn--outline" href="{{ route('workshops.register', $workshop) }}">
                    {{ __('site.workshops.register_waiting') }}
                    <span class="visually-hidden">: {{ $workshop->title }}</span>
                </a>
            @endif
        @endunless

        <a class="link-arrow" href="{{ route('workshops.show', $workshop) }}">
            {{ __('site.common.learn_more') }}
            <span class="visually-hidden">sur l’atelier {{ $workshop->title }}</span>
            <span aria-hidden="true">→</span>
        </a>
    </div>
</article>
