<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AgeRange;
use App\Enums\DeviceType;
use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Models\Municipality;
use App\Models\Workshop;
use App\Models\WorkshopRegistration;
use App\Services\WorkshopRegistrationService;
use App\Support\CsvExporter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Inscriptions aux ateliers, y compris celles reçues par téléphone (codex §10).
 */
class RegistrationController extends Controller
{
    public function __construct(
        protected WorkshopRegistrationService $registrations,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', WorkshopRegistration::class);

        $filters = $request->validate([
            'statut' => ['nullable', Rule::enum(RegistrationStatus::class)],
            'atelier' => ['nullable', 'integer', 'exists:workshops,id'],
            'recherche' => ['nullable', 'string', 'max:100'],
        ]);

        return view('admin.registrations.index', [
            'registrations' => $this->filteredQuery($filters)
                ->with(['workshop', 'municipality'])
                ->latest()
                ->paginate((int) config('site.per_page.admin'))
                ->withQueryString(),
            'filters' => $filters,
            'workshops' => Workshop::query()
                ->whereDate('date', '>=', today()->subMonths(3))
                ->orderByDesc('date')
                ->get(),
        ]);
    }

    /**
     * Formulaire d'inscription manuelle, pour une demande reçue au téléphone.
     */
    public function create(Workshop $workshop): View
    {
        $this->authorize('create', WorkshopRegistration::class);

        $workshop->loadCount([
            'registrations as active_registrations_count' => fn ($query) => $query->occupyingSeat(),
        ]);

        return view('admin.registrations.create', [
            'workshop' => $workshop,
            'municipalities' => Municipality::query()->ordered()->get(),
            'ageRanges' => AgeRange::options(),
            'devices' => DeviceType::options(),
        ]);
    }

    public function store(Request $request, Workshop $workshop): RedirectResponse
    {
        $this->authorize('create', WorkshopRegistration::class);

        $data = $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:180'],
            'municipality_id' => ['nullable', 'integer', 'exists:municipalities,id'],
            'municipality_name' => ['nullable', 'string', 'max:150'],
            'age_range' => ['nullable', Rule::enum(AgeRange::class)],
            'device' => ['nullable', Rule::enum(DeviceType::class)],
            'special_needs' => ['nullable', 'string', 'max:1000'],
            'voice_message_allowed' => ['nullable', 'boolean'],
            // Le consentement a été recueilli oralement : le conseiller
            // atteste l'avoir demandé, et cela reste tracé.
            'consent_confirmed' => ['accepted'],
        ], attributes: __('validation_custom.attributes'));

        unset($data['consent_confirmed']);
        $data['voice_message_allowed'] = $request->boolean('voice_message_allowed');

        $outcome = $this->registrations->register($workshop, $data, $request->user());

        return redirect()
            ->route('admin.workshops.participants', $workshop)
            ->with('status', $outcome->message())
            ->with('status_variant', $outcome->onWaitingList ? 'warning' : 'success');
    }

    public function update(Request $request, WorkshopRegistration $registration): RedirectResponse
    {
        $this->authorize('update', $registration);

        $data = $request->validate([
            'status' => ['required', Rule::enum(RegistrationStatus::class)],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
        ], attributes: [
            'status' => 'le statut',
            'internal_notes' => 'les notes internes',
        ]);

        $registration->update(['internal_notes' => $data['internal_notes'] ?? null]);

        try {
            $this->registrations->changeStatus(
                $registration,
                RegistrationStatus::from($data['status']),
            );
        } catch (\DomainException $exception) {
            return back()
                ->with('status', $exception->getMessage())
                ->with('status_variant', 'danger');
        }

        return back()
            ->with('status', __('admin.registrations.updated'))
            ->with('status_variant', 'success');
    }

    public function anonymise(WorkshopRegistration $registration): RedirectResponse
    {
        $this->authorize('anonymise', $registration);

        $registration->anonymise();

        return back()
            ->with('status', __('admin.registrations.anonymised'))
            ->with('status_variant', 'success');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', WorkshopRegistration::class);

        $filters = $request->validate([
            'statut' => ['nullable', Rule::enum(RegistrationStatus::class)],
            'atelier' => ['nullable', 'integer', 'exists:workshops,id'],
            'recherche' => ['nullable', 'string', 'max:100'],
        ]);

        return CsvExporter::stream(
            CsvExporter::filename('inscriptions-ateliers'),
            [
                'Référence', 'Atelier', 'Date de l’atelier', 'Statut', 'Prénom',
                'Nom', 'Téléphone', 'Courriel', 'Commune', 'Tranche d’âge',
                'Appareil', 'Inscrit le',
            ],
            $this->filteredQuery($filters)->with(['workshop', 'municipality']),
            fn (WorkshopRegistration $registration): array => [
                $registration->reference,
                $registration->workshop?->title,
                $registration->workshop?->date?->format('d/m/Y'),
                $registration->status->label(),
                $registration->first_name,
                $registration->last_name,
                $registration->phone,
                $registration->email,
                $registration->municipality?->name ?? $registration->municipality_name,
                $registration->age_range?->label(),
                $registration->device?->label(),
                $registration->created_at?->format('d/m/Y H:i'),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<WorkshopRegistration>
     */
    protected function filteredQuery(array $filters): Builder
    {
        return WorkshopRegistration::query()
            ->when($filters['statut'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['atelier'] ?? null, fn ($query, int $id) => $query->where('workshop_id', $id))
            ->when($filters['recherche'] ?? null, function ($query, string $terms): void {
                $query->where(function ($query) use ($terms): void {
                    $query->where('reference', 'like', "%{$terms}%")
                        ->orWhere('last_name', 'like', "%{$terms}%")
                        ->orWhere('first_name', 'like', "%{$terms}%")
                        ->orWhere('phone', 'like', "%{$terms}%");
                });
            });
    }
}
