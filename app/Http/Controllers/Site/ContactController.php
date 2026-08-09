<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreContactRequest;
use App\Http\Requests\Site\StorePartnershipRequest;
use App\Models\Location;
use App\Models\Municipality;
use App\Models\Partner;
use App\Services\ContactRequestService;
use App\Services\SettingsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Formulaires de contact et de partenariat.
 */
class ContactController extends Controller
{
    public function __construct(
        protected ContactRequestService $requests,
    ) {}

    public function create(): View
    {
        return view('site.contact.create', [
            'municipalities' => Municipality::query()->covered()->ordered()->get(),
            'locations' => Location::query()->active()->with('municipality')->get(),
        ]);
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $contact = $this->requests->createContact($request->contactData());

        return redirect()
            ->route('contact.create')
            ->with('status', __('site.contact.sent', ['reference' => $contact->reference]))
            ->with('status_variant', 'success');
    }

    public function partnership(): View
    {
        return view('site.partnership.create', [
            'municipalities' => Municipality::query()->covered()->ordered()->get(),
            'partners' => Partner::query()->published()->ordered()->get(),
            'needs' => StorePartnershipRequest::needOptions(),
        ]);
    }

    /**
     * Présentation imprimable des interventions.
     *
     * Une collectivité ne décide pas devant un écran : la demande remonte à
     * un élu, en conseil ou en commission. Cette page existe pour être
     * imprimée ou transmise en pièce jointe.
     */
    public function brochure(): View
    {
        return view('site.partnership.brochure', [
            'municipalities' => Municipality::query()->covered()->ordered()->get(),
        ]);
    }

    /**
     * Plaquette grand public, au format A5.
     *
     * Destinée à être déposée en mairie, en médiathèque, en pharmacie ou dans
     * une salle d'attente. Elle ne s'adresse pas aux mêmes personnes que la
     * présentation professionnelle, et ne dit donc pas la même chose.
     */
    public function leaflet(SettingsService $settings): View
    {
        return view('site.leaflet', [
            'municipalities' => Municipality::query()->covered()->ordered()->get(),
            'openingHours' => $settings->openingHours(),
        ]);
    }

    public function storePartnership(StorePartnershipRequest $request): RedirectResponse
    {
        $partnership = $this->requests->createPartnership($request->partnershipData());

        return redirect()
            ->route('partnership.create')
            ->with('status', __('site.partnership.sent', ['reference' => $partnership->reference]))
            ->with('status_variant', 'success');
    }
}
