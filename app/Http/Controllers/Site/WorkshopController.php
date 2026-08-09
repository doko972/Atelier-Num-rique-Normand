<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreWorkshopRegistrationRequest;
use App\Models\Municipality;
use App\Models\Workshop;
use App\Models\WorkshopCategory;
use App\Services\WorkshopRegistrationService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Agenda public des ateliers et inscription (codex §10).
 */
class WorkshopController extends Controller
{
    public function __construct(
        protected WorkshopRegistrationService $registrations,
    ) {}

    public function index(Request $request): View
    {
        $validated = $request->validate([
            'categorie' => ['nullable', 'string', 'exists:workshop_categories,slug'],
            'commune' => ['nullable', 'string', 'exists:municipalities,slug'],
        ]);

        $workshops = Workshop::query()
            ->public()
            ->upcoming()
            ->with(['category', 'location', 'municipality'])
            ->withCount([
                'registrations as active_registrations_count' => fn ($query) => $query->occupyingSeat(),
            ])
            ->when(
                $validated['categorie'] ?? null,
                fn ($query, string $slug) => $query->whereHas(
                    'category',
                    fn ($category) => $category->where('slug', $slug),
                ),
            )
            ->when(
                $validated['commune'] ?? null,
                fn ($query, string $slug) => $query->whereHas(
                    'municipality',
                    fn ($municipality) => $municipality->where('slug', $slug),
                ),
            )
            ->paginate((int) config('site.per_page.public'))
            ->withQueryString();

        return view('site.workshops.index', [
            'workshops' => $workshops,
            'categories' => WorkshopCategory::query()->published()->ordered()->get(),
            'municipalities' => Municipality::query()->covered()->ordered()->get(),
            'filters' => $validated,
            'pastWorkshops' => Workshop::query()
                ->public()
                ->past()
                ->limit(4)
                ->get(),
        ]);
    }

    public function show(Workshop $workshop): View
    {
        abort_unless($workshop->status->isPublic(), 404);

        $workshop->loadCount([
            'registrations as active_registrations_count' => fn ($query) => $query->occupyingSeat(),
        ]);

        return view('site.workshops.show', [
            'workshop' => $workshop->load(['category', 'location.municipality', 'partner', 'files']),
            'related' => Workshop::query()
                ->public()
                ->upcoming()
                ->whereKeyNot($workshop->getKey())
                ->withCount([
                    'registrations as active_registrations_count' => fn ($query) => $query->occupyingSeat(),
                ])
                ->limit(3)
                ->get(),
        ]);
    }

    /**
     * Formulaire d'inscription.
     */
    public function create(Workshop $workshop): View
    {
        abort_unless($workshop->status->isPublic(), 404);

        $workshop->loadCount([
            'registrations as active_registrations_count' => fn ($query) => $query->occupyingSeat(),
        ]);

        abort_unless(
            $workshop->registrationsOpen() || $workshop->waitingListOpen(),
            410,
            __('site.workshops.registrations_closed'),
        );

        return view('site.workshops.register', [
            'workshop' => $workshop->load('location'),
            'municipalities' => Municipality::query()->covered()->ordered()->get(),
        ]);
    }

    public function store(StoreWorkshopRegistrationRequest $request, Workshop $workshop): RedirectResponse
    {
        abort_unless($workshop->status->isPublic(), 404);

        $workshop->loadCount([
            'registrations as active_registrations_count' => fn ($query) => $query->occupyingSeat(),
        ]);

        abort_unless(
            $workshop->registrationsOpen() || $workshop->waitingListOpen(),
            410,
            __('site.workshops.registrations_closed'),
        );

        $outcome = $this->registrations->register($workshop, $request->registrationData());

        return redirect()
            ->route('workshops.show', $workshop)
            ->with('status', $outcome->message())
            ->with('status_variant', $outcome->onWaitingList ? 'warning' : 'success');
    }
}
