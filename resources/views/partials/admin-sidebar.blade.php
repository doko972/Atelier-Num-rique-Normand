@php
    use App\Enums\Permission;

    $user = auth()->user();

    // Les compteurs ne sont calculés que pour les entrées réellement visibles.
    $counts = [
        'appointments' => $user->hasPermission(Permission::ManageAppointments)
            ? \App\Models\Appointment::query()->needsAttention()->count()
            : 0,
        'contact_requests' => $user->hasPermission(Permission::ManageContactRequests)
            ? \App\Models\ContactRequest::query()->open()->count()
            : 0,
        'partnership_requests' => $user->hasPermission(Permission::ManagePartnershipRequests)
            ? \App\Models\PartnershipRequest::query()->open()->count()
            : 0,
        'gdpr' => $user->hasPermission(Permission::ManageGdprRequests)
            ? \App\Models\DataExportRequest::query()->open()->count()
                + \App\Models\DataDeletionRequest::query()->open()->count()
            : 0,
    ];

    $sections = [
        'daily' => [
            'permission' => null,
            'items' => [
                ['route' => 'admin.dashboard', 'label' => __('admin.nav.dashboard'), 'permission' => Permission::ViewDashboard],
                ['route' => 'admin.appointments.index', 'label' => __('admin.nav.appointments'), 'permission' => Permission::ManageAppointments, 'count' => $counts['appointments']],
                ['route' => 'admin.workshops.index', 'label' => __('admin.nav.workshops'), 'permission' => Permission::ManageWorkshops],
                ['route' => 'admin.registrations.index', 'label' => __('admin.nav.registrations'), 'permission' => Permission::ManageRegistrations],
                ['route' => 'admin.contact-requests.index', 'label' => __('admin.nav.contact_requests'), 'permission' => Permission::ManageContactRequests, 'count' => $counts['contact_requests']],
                ['route' => 'admin.partnership-requests.index', 'label' => __('admin.nav.partnership_requests'), 'permission' => Permission::ManagePartnershipRequests, 'count' => $counts['partnership_requests']],
            ],
        ],
        'content' => [
            'items' => [
                ['route' => 'admin.services.index', 'label' => __('admin.nav.services'), 'permission' => Permission::ManageContent],
                ['route' => 'admin.service-categories.index', 'label' => __('admin.nav.service_categories'), 'permission' => Permission::ManageContent],
                ['route' => 'admin.workshop-categories.index', 'label' => __('admin.nav.workshop_categories'), 'permission' => Permission::ManageContent],
                ['route' => 'admin.guides.index', 'label' => __('admin.nav.guides'), 'permission' => Permission::ManageContent],
                ['route' => 'admin.articles.index', 'label' => __('admin.nav.articles'), 'permission' => Permission::ManageContent],
                ['route' => 'admin.article-categories.index', 'label' => __('admin.nav.article_categories'), 'permission' => Permission::ManageContent],
                ['route' => 'admin.pages.index', 'label' => __('admin.nav.pages'), 'permission' => Permission::ManageContent],
                ['route' => 'admin.faqs.index', 'label' => __('admin.nav.faqs'), 'permission' => Permission::ManageContent],
                ['route' => 'admin.testimonials.index', 'label' => __('admin.nav.testimonials'), 'permission' => Permission::ManageContent],
                ['route' => 'admin.pricings.index', 'label' => __('admin.nav.pricings'), 'permission' => Permission::ManageContent],
                ['route' => 'admin.official-links.index', 'label' => __('admin.nav.official_links'), 'permission' => Permission::ManageContent],
            ],
        ],
        'directory' => [
            'items' => [
                ['route' => 'admin.municipalities.index', 'label' => __('admin.nav.municipalities'), 'permission' => Permission::ManageDirectory],
                ['route' => 'admin.locations.index', 'label' => __('admin.nav.locations'), 'permission' => Permission::ManageDirectory],
                ['route' => 'admin.partners.index', 'label' => __('admin.nav.partners'), 'permission' => Permission::ManageDirectory],
            ],
        ],
        'settings' => [
            'items' => [
                ['route' => 'admin.users.index', 'label' => __('admin.nav.users'), 'permission' => Permission::ManageUsers],
                ['route' => 'admin.settings.edit', 'label' => __('admin.nav.settings_link'), 'permission' => Permission::ManageSettings],
                ['route' => 'admin.gdpr.index', 'label' => __('admin.nav.gdpr'), 'permission' => Permission::ManageGdprRequests, 'count' => $counts['gdpr']],
                ['route' => 'admin.audit-logs.index', 'label' => __('admin.nav.audit_logs'), 'permission' => Permission::ViewAuditLog],
            ],
        ],
    ];
@endphp

<nav class="admin-sidebar no-print" aria-label="{{ __('admin.title') }}">
    <a class="admin-sidebar__brand" href="{{ route('admin.dashboard') }}">
        {{--
            Le monogramme blanc si sa déclinaison existe — le menu est bleu
            foncé —, sinon le monogramme couleur, que la feuille de style pose
            alors sur une pastille claire.

            Le monogramme et non le logo complet : à 32 pixels, les trois
            lignes du nom seraient illisibles.
        --}}
        @php $whiteMark = \App\Support\Branding::has(\App\Support\Branding::MARK_LIGHT); @endphp

        <x-site-logo
            class="admin-sidebar__logo {{ $whiteMark ? 'admin-sidebar__logo--transparent' : '' }}"
            :variant="$whiteMark ? \App\Support\Branding::MARK_LIGHT : \App\Support\Branding::MARK"
            :width="32"
            :height="32"
        />
        <span>{{ __('admin.title') }}</span>
    </a>

    @foreach ($sections as $key => $section)
        @php
            $visible = collect($section['items'])
                ->filter(fn (array $item): bool => $user->hasPermission($item['permission']));
        @endphp

        @if ($visible->isNotEmpty())
            <div>
                <p class="admin-sidebar__section-title" id="menu-{{ $key }}">{{ __("admin.nav.{$key}") }}</p>

                <ul class="admin-sidebar__list" aria-labelledby="menu-{{ $key }}">
                    @foreach ($visible as $item)
                        <li>
                            <a
                                class="admin-sidebar__link"
                                href="{{ route($item['route']) }}"
                                @if (request()->routeIs($item['route'])) aria-current="page" @endif
                            >
                                <span>{{ $item['label'] }}</span>

                                @if (($item['count'] ?? 0) > 0)
                                    <span class="admin-sidebar__count">
                                        {{ $item['count'] }}
                                        <span class="visually-hidden">à traiter</span>
                                    </span>
                                @endif
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif
    @endforeach
</nav>
