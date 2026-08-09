@php
    $settings = app(\App\Services\SettingsService::class);

    // Menu court et peu profond : aucun sous-menu déroulant (codex §6).
    $links = [
        ['route' => 'services.index', 'label' => __('site.nav.services'), 'active' => 'mes-services*'],
        ['route' => 'workshops.index', 'label' => __('site.nav.workshops'), 'active' => 'ateliers*'],
        ['route' => 'procedures', 'label' => __('site.nav.procedures'), 'active' => 'demarches-en-ligne'],
        ['route' => 'security', 'label' => __('site.nav.security'), 'active' => 'securite-et-arnaques'],
        ['route' => 'resources.index', 'label' => __('site.nav.resources'), 'active' => 'conseils-pratiques*'],
        ['route' => 'pricing', 'label' => __('site.nav.pricing'), 'active' => 'tarifs'],
        ['route' => 'partnership.create', 'label' => __('site.nav.partnership'), 'active' => 'partenariats'],
        ['route' => 'about', 'label' => __('site.nav.about'), 'active' => 'a-propos'],
        ['route' => 'contact.create', 'label' => __('site.nav.contact'), 'active' => 'contact'],
    ];
@endphp

<header class="site-header no-print">
    <div class="site-header__inner">
        <a class="site-header__brand" href="{{ route('home') }}">
            {{--
                Le monogramme seul : le nom du service est déjà écrit en
                toutes lettres à côté, et à cette taille les trois lignes du
                logo complet seraient illisibles.
            --}}
            <x-site-logo
                class="site-header__logo"
                :variant="\App\Support\Branding::MARK"
                :width="48"
                :height="48"
            />

            <span class="site-header__brand-text">
                <span class="site-header__brand-name">{{ $settings->string('site_name') }}</span>
                <span class="site-header__brand-tagline">{{ $settings->string('site_tagline') }}</span>
            </span>
        </a>

        <div class="site-header__actions">
            @if ($settings->hasPhone())
                <a class="site-header__phone" href="{{ $settings->phoneLink() }}">
                    <x-icon name="telephone" width="20" height="20" />
                    <span class="site-header__phone-label">{{ __('site.call.label') }}</span>
                    <span>{{ $settings->phoneDisplay() }}</span>
                </a>
            @endif

            {{--
                aria-expanded="false" par défaut : sans JavaScript, le script
                ne s'exécute pas, le tiroir reste déplié et ce bouton est
                masqué par la feuille de style. Aucun bouton inerte n'est donc
                proposé.
            --}}
            <button
                type="button"
                class="site-header__toggle"
                data-menu-toggle
                data-label-open="{{ __('site.nav.toggle') }}"
                data-label-close="{{ __('site.nav.close') }}"
                aria-expanded="false"
                aria-controls="menu-principal"
            >
                <span class="site-header__toggle-icon" aria-hidden="true"></span>
                <span data-menu-toggle-label>{{ __('site.nav.toggle') }}</span>
            </button>
        </div>
    </div>

    {{--
        Le tiroir est rendu déplié : c'est le script qui pose `data-enhanced`
        et bascule alors le comportement en panneau latéral.
    --}}
    <div class="drawer" id="menu-principal" data-drawer data-open="false">
        <div class="drawer__backdrop" data-drawer-backdrop></div>

        <div class="drawer__panel" data-drawer-panel>
            <div class="drawer__header">
                <p class="drawer__title" id="titre-menu">{{ __('site.nav.label') }}</p>

                <button type="button" class="drawer__close" data-drawer-close>
                    <span aria-hidden="true">✕</span>
                    {{ __('site.nav.close') }}
                </button>
            </div>

            <nav aria-labelledby="titre-menu">
                <ul class="main-nav__list">
                    @foreach ($links as $link)
                        <li>
                            <a
                                class="main-nav__link"
                                href="{{ route($link['route']) }}"
                                @if (request()->is($link['active'])) aria-current="page" @endif
                            >{{ $link['label'] }}</a>
                        </li>
                    @endforeach

                    <li>
                        <a
                            class="main-nav__link main-nav__link--cta"
                            href="{{ route('appointments.create') }}"
                            @if (request()->is('prendre-rendez-vous*')) aria-current="page" @endif
                        >{{ __('site.nav.appointment') }}</a>
                    </li>
                </ul>
            </nav>

            @if ($settings->hasPhone())
                <div class="drawer__footer">
                    <a class="drawer__phone" href="{{ $settings->phoneLink() }}">
                        <x-icon name="telephone" width="24" height="24" />
                        {{ $settings->phoneDisplay() }}
                    </a>

                    <p class="drawer__note">
                        @if ($settings->isOpenAt())
                            {{ __('site.call.open') }}
                        @else
                            {{ __('site.call.closed') }}
                        @endif
                    </p>
                </div>
            @endif
        </div>
    </div>
</header>
