<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\AppointmentStatus;
use App\Enums\AppointmentType;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Location;
use App\Models\User;
use App\Services\AppointmentService;
use App\Support\CsvExporter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Suivi administratif des demandes de rendez-vous (codex §11).
 */
class AppointmentController extends Controller
{
    public function __construct(
        protected AppointmentService $appointments,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Appointment::class);

        $filters = $request->validate([
            'statut' => ['nullable', Rule::enum(AppointmentStatus::class)],
            'type' => ['nullable', Rule::enum(AppointmentType::class)],
            'conseiller' => ['nullable', 'integer', 'exists:users,id'],
            'recherche' => ['nullable', 'string', 'max:100'],
            'a_rappeler' => ['nullable', 'boolean'],
        ]);

        return view('admin.appointments.index', [
            'appointments' => $this->filteredQuery($filters)
                ->with(['municipality', 'assignee'])
                ->latest()
                ->paginate((int) config('site.per_page.admin'))
                ->withQueryString(),
            'filters' => $filters,
            'advisers' => User::query()->advisers()->orderBy('name')->get(),
        ]);
    }

    public function show(Appointment $appointment): View
    {
        $this->authorize('view', $appointment);

        return view('admin.appointments.show', [
            'appointment' => $appointment->load([
                'municipality',
                'location',
                'assignee',
                'notes.author',
                'consentLogs',
            ]),
            'advisers' => User::query()->advisers()->orderBy('name')->get(),
            'locations' => Location::query()->active()->orderBy('name')->get(),
            'transitions' => $appointment->status->allowedTransitions(),
        ]);
    }

    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorize('update', $appointment);

        $data = $request->validate([
            'status' => ['required', Rule::enum(AppointmentStatus::class)],
            'notify' => ['nullable', 'boolean'],
            'callback_on' => ['nullable', 'date'],
            'scheduled_for' => ['nullable', 'date'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
        ], attributes: [
            'status' => 'le statut',
            'callback_on' => 'la date de rappel',
            'scheduled_for' => 'la date du rendez-vous',
            'location_id' => 'le lieu',
        ]);

        $appointment->fill([
            'callback_on' => $data['callback_on'] ?? null,
            'scheduled_for' => $data['scheduled_for'] ?? null,
            'location_id' => $data['location_id'] ?? null,
        ])->save();

        try {
            $this->appointments->changeStatus(
                $appointment,
                AppointmentStatus::from($data['status']),
                $request->user(),
                $request->boolean('notify'),
            );
        } catch (\DomainException $exception) {
            return back()
                ->with('status', $exception->getMessage())
                ->with('status_variant', 'danger');
        }

        return back()
            ->with('status', __('admin.appointments.updated'))
            ->with('status_variant', 'success');
    }

    public function assign(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorize('update', $appointment);

        $data = $request->validate([
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
        ], attributes: ['assigned_to' => 'le conseiller']);

        $this->appointments->assign(
            $appointment,
            $data['assigned_to'] === null ? null : User::findOrFail($data['assigned_to']),
            $request->user(),
        );

        return back()
            ->with('status', __('admin.appointments.assigned'))
            ->with('status_variant', 'success');
    }

    public function addNote(Request $request, Appointment $appointment): RedirectResponse
    {
        $this->authorize('update', $appointment);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ], attributes: ['body' => 'la note']);

        $this->appointments->addNote($appointment, $data['body'], $request->user());

        return back()
            ->with('status', __('admin.appointments.note_added'))
            ->with('status_variant', 'success');
    }

    /**
     * Anonymisation manuelle, en réponse à une demande RGPD.
     */
    public function anonymise(Appointment $appointment): RedirectResponse
    {
        $this->authorize('anonymise', $appointment);

        $appointment->anonymise();

        return back()
            ->with('status', __('admin.appointments.anonymised'))
            ->with('status_variant', 'success');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', Appointment::class);

        $filters = $request->validate([
            'statut' => ['nullable', Rule::enum(AppointmentStatus::class)],
            'type' => ['nullable', Rule::enum(AppointmentType::class)],
            'conseiller' => ['nullable', 'integer', 'exists:users,id'],
            'recherche' => ['nullable', 'string', 'max:100'],
            'a_rappeler' => ['nullable', 'boolean'],
        ]);

        return CsvExporter::stream(
            CsvExporter::filename('demandes-de-rendez-vous'),
            [
                'Référence', 'Reçue le', 'Statut', 'Type', 'Prénom', 'Nom',
                'Téléphone', 'Courriel', 'Commune', 'Besoin', 'Disponibilités',
                'Déplacement à domicile', 'Conseiller', 'Rappel prévu le',
            ],
            $this->filteredQuery($filters)->with(['municipality', 'assignee']),
            fn (Appointment $appointment): array => [
                $appointment->reference,
                $appointment->created_at?->format('d/m/Y H:i'),
                $appointment->status->label(),
                $appointment->type->label(),
                $appointment->first_name,
                $appointment->last_name,
                $appointment->phone,
                $appointment->email,
                $appointment->municipality?->name ?? $appointment->municipality_name,
                $appointment->need_description,
                $appointment->availability,
                $appointment->home_visit_requested,
                $appointment->assignee?->name,
                $appointment->callback_on?->format('d/m/Y'),
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Appointment>
     */
    protected function filteredQuery(array $filters): Builder
    {
        return Appointment::query()
            ->when($filters['statut'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->when($filters['conseiller'] ?? null, fn ($query, int $id) => $query->where('assigned_to', $id))
            ->when($filters['a_rappeler'] ?? null, fn ($query) => $query->open()->whereDate('callback_on', '<=', today()))
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
