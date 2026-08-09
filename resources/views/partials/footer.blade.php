@php
    $settings = app(\App\Services\SettingsService::class);
    $footerPages = \App\Models\Page::query()->inFooter()->get();
    $hours = $settings->openingHours();
@endphp

<footer class="site-footer" id="pied-de-page" tabindex="-1">
    <div class="site-footer__grid">
        <div>
            <h2>{{ __('site.footer.about_title') }}</h2>
            <p>{{ $settings->string('site_tagline') }}</p>

            @if ($settings->string('adviser_name'))
                <p>{{ $settings->string('adviser_name') }}</p>
            @endif
        </div>

        <div>
            <h2>{{ __('site.footer.contact_title') }}</h2>

            @if ($settings->hasPhone())
                <a class="site-footer__phone" href="{{ $settings->phoneLink() }}">
                    {{ $settings->phoneDisplay() }}
                </a>
            @endif

            @if ($settings->string('email'))
                <p><a href="mailto:{{ $settings->string('email') }}">{{ $settings->string('email') }}</a></p>
            @endif

            @if ($hours->isNotEmpty())
                <h3>{{ __('site.call.hours_title') }}</h3>
                <ul class="site-footer__list">
                    @foreach ($hours as $hour)
                        <li>
                            <span>{{ ucfirst($hour->weekdayName()) }} : {{ $hour->range() }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>

        <div>
            <h2>{{ __('site.footer.quick_title') }}</h2>
            <ul class="site-footer__list">
                <li><a href="{{ route('appointments.create') }}">{{ __('site.nav.appointment') }}</a></li>
                <li><a href="{{ route('workshops.index') }}">{{ __('site.nav.workshops') }}</a></li>
                <li><a href="{{ route('resources.index') }}">{{ __('site.nav.resources') }}</a></li>
                <li><a href="{{ route('faq') }}">{{ __('site.nav.faq') }}</a></li>
                <li><a href="{{ route('partnership.create') }}">{{ __('site.nav.partnership') }}</a></li>
            </ul>
        </div>

        <div>
            <h2>{{ __('site.footer.legal_title') }}</h2>
            <ul class="site-footer__list">
                <li><a href="{{ route('legal') }}">Mentions légales</a></li>
                <li><a href="{{ route('privacy') }}">Politique de confidentialité</a></li>
                <li><a href="{{ route('cookies') }}">Gestion des cookies</a></li>
                <li><a href="{{ route('accessibility') }}">Déclaration d’accessibilité</a></li>

                @foreach ($footerPages as $page)
                    <li><a href="{{ route('pages.show', $page) }}">{{ $page->title }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="site-footer__bottom">
        <p>{{ __('site.footer.copyright', ['year' => now()->year, 'name' => $settings->string('site_name')]) }}</p>
        <p>{{ __('site.footer.made_with') }}</p>
    </div>
</footer>
