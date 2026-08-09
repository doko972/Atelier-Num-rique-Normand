<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Enums\SkillLevel;
use App\Enums\WorkshopStatus;
use App\Http\Controllers\Controller;
use App\Models\Location;
use App\Models\Municipality;
use App\Models\Partner;
use App\Models\Workshop;
use App\Models\WorkshopCategory;
use App\Models\WorkshopRegistration;
use App\Notifications\WorkshopCancelledNotification;
use App\Support\CsvExporter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Gestion des ateliers collectifs.
 */
class WorkshopController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Workshop::class);

        $filters = $request->validate([
            'statut' => ['nullable', Rule::enum(WorkshopStatus::class)],
            'recherche' => ['nullable', 'string', 'max:100'],
            'periode' => ['nullable', 'in:a-venir,passes'],
        ]);

        $workshops = Workshop::query()
            ->with(['category', 'location', 'municipality'])
            ->withCount([
                'registrations as active_registrations_count' => fn ($query) => $query->occupyingSeat(),
                'registrations as waiting_count' => fn ($query) => $query->where('status', RegistrationStatus::WaitingList),
            ])
            ->when($filters['statut'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when(
                $filters['recherche'] ?? null,
                fn ($query, string $terms) => $query->where('title', 'like', "%{$terms}%"),
            )
            ->when(
                ($filters['periode'] ?? 'a-venir') === 'passes',
                fn ($query) => $query->past(),
                fn ($query) => $query->whereDate('date', '>=', today())->orderBy('date')->orderBy('start_time'),
            )
            ->paginate((int) config('site.per_page.admin'))
            ->withQueryString();

        return view('admin.workshops.index', [
            'workshops' => $workshops,
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Workshop::class);

        return view('admin.workshops.form', [
            'workshop' => new Workshop(['capacity' => 8, 'is_free' => true, 'waiting_list_enabled' => true]),
            'isNew' => true,
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Workshop::class);

        $workshop = Workshop::create($this->validated($request));

        Log::channel('ateliers')->info('Atelier créé.', ['slug' => $workshop->slug]);

        return redirect()
            ->route('admin.workshops.index')
            ->with('status', __('admin.workshops.created'))
            ->with('status_variant', 'success');
    }

    public function edit(Workshop $workshop): View
    {
        $this->authorize('update', $workshop);

        return view('admin.workshops.form', [
            'workshop' => $workshop,
            'isNew' => false,
            ...$this->formOptions(),
        ]);
    }

    public function update(Request $request, Workshop $workshop): RedirectResponse
    {
        $this->authorize('update', $workshop);

        $workshop->update($this->validated($request, $workshop));

        return redirect()
            ->route('admin.workshops.index')
            ->with('status', __('admin.workshops.updated'))
            ->with('status_variant', 'success');
    }

    public function destroy(Workshop $workshop): RedirectResponse
    {
        $this->authorize('delete', $workshop);

        $workshop->delete();

        return redirect()
            ->route('admin.workshops.index')
            ->with('status', __('admin.workshops.deleted'))
            ->with('status_variant', 'success');
    }

    /**
     * Annulation : les personnes inscrites en sont informées.
     */
    public function cancel(Request $request, Workshop $workshop): RedirectResponse
    {
        $this->authorize('cancel', $workshop);

        $data = $request->validate([
            'cancellation_reason' => ['required', 'string', 'max:1000'],
        ], attributes: ['cancellation_reason' => 'le motif d’annulation']);

        $workshop->update([
            'status' => WorkshopStatus::Cancelled,
            'cancellation_reason' => $data['cancellation_reason'],
        ]);

        $notified = 0;

        foreach ($workshop->registrations()->get() as $registration) {
            if ($registration->canReceiveEmail()) {
                Notification::route('mail', $registration->email)
                    ->notify(new WorkshopCancelledNotification($registration));
                $notified++;
            }
        }

        Log::channel('ateliers')->notice('Atelier annulé.', [
            'slug' => $workshop->slug,
            'notified' => $notified,
        ]);

        return back()
            ->with('status', __('admin.workshops.cancelled', ['count' => $notified]))
            ->with('status_variant', 'warning');
    }

    /**
     * Liste des participants, imprimable et exportable.
     */
    public function participants(Workshop $workshop): View
    {
        $this->authorize('view', $workshop);

        return view('admin.workshops.participants', [
            'workshop' => $workshop,
            'registrations' => $workshop->registrations()
                ->with('municipality')
                ->orderBy('status')
                ->orderBy('waiting_position')
                ->orderBy('last_name')
                ->get(),
        ]);
    }

    public function exportParticipants(Workshop $workshop): StreamedResponse
    {
        $this->authorize('export', Workshop::class);

        return CsvExporter::stream(
            CsvExporter::filename('participants-'.$workshop->slug),
            [
                'Référence', 'Statut', 'Prénom', 'Nom', 'Téléphone', 'Courriel',
                'Commune', 'Tranche d’âge', 'Appareil', 'Besoin particulier',
                'Position en attente', 'Inscrit par téléphone',
            ],
            $workshop->registrations()->with('municipality')->getQuery(),
            fn (WorkshopRegistration $registration): array => [
                $registration->reference,
                $registration->status->label(),
                $registration->first_name,
                $registration->last_name,
                $registration->phone,
                $registration->email,
                $registration->municipality?->name ?? $registration->municipality_name,
                $registration->age_range?->label(),
                $registration->device?->label(),
                $registration->special_needs,
                $registration->waiting_position,
                $registration->registered_by_phone,
            ],
        );
    }

    /**
     * Options communes aux formulaires de création et de modification.
     *
     * @return array<string, mixed>
     */
    protected function formOptions(): array
    {
        return [
            'categories' => WorkshopCategory::query()->ordered()->get(),
            'locations' => Location::query()->active()->orderBy('name')->get(),
            'municipalities' => Municipality::query()->ordered()->get(),
            'partners' => Partner::query()->ordered()->get(),
            'levels' => SkillLevel::options(),
            'statuses' => WorkshopStatus::options(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Workshop $workshop = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'slug' => [
                'nullable', 'string', 'max:220',
                Rule::unique('workshops', 'slug')->ignore($workshop?->getKey()),
            ],
            'workshop_category_id' => ['nullable', 'integer', 'exists:workshop_categories,id'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'municipality_id' => ['nullable', 'integer', 'exists:municipalities,id'],
            'partner_id' => ['nullable', 'integer', 'exists:partners,id'],

            'description' => ['required', 'string', 'max:5000'],
            'objectives' => ['nullable', 'string', 'max:2000'],
            'prerequisites' => ['nullable', 'string', 'max:2000'],
            'level' => ['required', Rule::enum(SkillLevel::class)],

            'date' => ['required', 'date'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'registration_deadline' => ['nullable', 'date', 'before_or_equal:date'],

            'capacity' => ['required', 'integer', 'min:1', 'max:200'],
            'waiting_list_enabled' => ['nullable', 'boolean'],

            'is_accessible' => ['nullable', 'boolean'],
            'equipment_provided' => ['nullable', 'boolean'],
            'own_device_allowed' => ['nullable', 'boolean'],
            'is_free' => ['nullable', 'boolean'],
            'price_cents' => ['nullable', 'integer', 'min:0', 'max:1000000'],

            'instructor_name' => ['nullable', 'string', 'max:150'],
            'image_alt' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(WorkshopStatus::class)],
            'meta_title' => ['nullable', 'string', 'max:180'],
            'meta_description' => ['nullable', 'string', 'max:255'],
        ], attributes: __('admin.workshops.attributes'));

        foreach ([
            'waiting_list_enabled', 'is_accessible', 'equipment_provided',
            'own_device_allowed', 'is_free',
        ] as $flag) {
            $data[$flag] = $request->boolean($flag);
        }

        // Les objectifs sont saisis une ligne à la fois.
        $data['objectives'] = collect(preg_split('/\r\n|\r|\n/', (string) ($data['objectives'] ?? '')))
            ->map(fn (string $line): string => trim($line))
            ->filter()
            ->values()
            ->all();

        if ($data['status'] === WorkshopStatus::Published->value) {
            $data['published_at'] = $workshop?->published_at ?? now();
        }

        return $data;
    }
}
