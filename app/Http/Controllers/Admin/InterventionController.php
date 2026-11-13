<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\InterventionActivity;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Intervention;
use App\Services\SapComplianceService;
use App\Support\CsvExporter;
use App\Support\FrenchFormat;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Registre des prestations réalisées et bilan SAP / hors SAP.
 *
 * Le registre est la comptabilité séparée qu'exige la dispense d'exclusivité
 * SAP ; le bilan en tire la répartition 70 / 30 et les chiffres des états
 * d'activité trimestriels.
 */
class InterventionController extends Controller
{
    public function __construct(
        protected SapComplianceService $sap,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Intervention::class);

        $filters = $this->validateFilters($request);
        $year = (int) ($filters['annee'] ?? now()->year);

        return view('admin.interventions.index', [
            'interventions' => $this->filteredQuery($filters, $year)
                ->with('appointment')
                ->orderByDesc('performed_on')
                ->orderByDesc('id')
                ->paginate((int) config('site.per_page.admin'))
                ->withQueryString(),
            'filters' => $filters,
            'year' => $year,
            'years' => $this->sap->availableYears(),
            'summary' => $this->sap->yearSummary($year),
        ]);
    }

    /**
     * Bilan annuel et chiffres trimestriels.
     */
    public function report(Request $request): View
    {
        $this->authorize('viewAny', Intervention::class);

        $year = $this->validatedYear($request);

        return view('admin.interventions.report', [
            'year' => $year,
            'years' => $this->sap->availableYears(),
            'summary' => $this->sap->yearSummary($year),
            'quarters' => $this->sap->quarterlyBreakdown($year),
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Intervention::class);

        $intervention = new Intervention([
            'performed_on' => today(),
            'duration_minutes' => 60,
            'activity' => InterventionActivity::HomeTraining,
        ]);

        // Prestation issue d'une demande de rendez-vous : on reprend ce qui
        // est déjà connu pour éviter la double saisie.
        $appointment = $request->filled('rendez_vous')
            ? Appointment::query()->find($request->integer('rendez_vous'))
            : null;

        if ($appointment !== null) {
            $intervention->fill([
                'appointment_id' => $appointment->id,
                'client_name' => $appointment->isAnonymised() ? null : $appointment->fullName(),
                'performed_on' => $appointment->scheduled_for ?? today(),
                'activity' => InterventionActivity::suggestedFor($appointment->type),
            ]);
        }

        return view('admin.interventions.form', [
            'intervention' => $intervention,
            'appointment' => $appointment,
            'isNew' => true,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Intervention::class);

        $intervention = Intervention::create($this->validated($request));

        return $this->redirectAfterSave($intervention, __('admin.interventions.created'));
    }

    public function edit(Intervention $intervention): View
    {
        $this->authorize('update', $intervention);

        return view('admin.interventions.form', [
            'intervention' => $intervention,
            'appointment' => $intervention->appointment,
            'isNew' => false,
        ]);
    }

    public function update(Request $request, Intervention $intervention): RedirectResponse
    {
        $this->authorize('update', $intervention);

        $intervention->update($this->validated($request));

        return $this->redirectAfterSave($intervention, __('admin.interventions.updated'));
    }

    public function destroy(Intervention $intervention): RedirectResponse
    {
        $this->authorize('delete', $intervention);

        $intervention->delete();

        return redirect()
            ->route('admin.interventions.index', ['annee' => $intervention->performed_on->year])
            ->with('status', __('admin.interventions.deleted'))
            ->with('status_variant', 'success');
    }

    public function restore(int $id): RedirectResponse
    {
        $intervention = Intervention::onlyTrashed()->findOrFail($id);

        $this->authorize('restore', $intervention);

        $intervention->restore();

        return redirect()
            ->route('admin.interventions.index', ['annee' => $intervention->performed_on->year])
            ->with('status', __('admin.interventions.restored'))
            ->with('status_variant', 'success');
    }

    /**
     * Détail des prestations de l'année : la pièce à remettre au comptable.
     */
    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', Intervention::class);

        $filters = $this->validateFilters($request);
        $year = (int) ($filters['annee'] ?? now()->year);

        return CsvExporter::stream(
            CsvExporter::filename("prestations-{$year}"),
            [
                'Référence', 'Date', 'Nature', 'Services à la personne', 'Activité SAP',
                'Client', 'Durée (heures)', 'Montant (€)', 'N° de facture', 'Rendez-vous',
            ],
            $this->filteredQuery($filters, $year)->with('appointment')->orderBy('performed_on'),
            fn (Intervention $intervention): array => [
                $intervention->reference,
                $intervention->performed_on->format('d/m/Y'),
                $intervention->activity->label(),
                $intervention->isSap() ? 'oui' : 'non',
                $intervention->activity->sapGroup() === null
                    ? ''
                    : __('admin.interventions.groups.'.$intervention->activity->sapGroup()),
                $intervention->client_name,
                number_format($intervention->duration_minutes / 60, 2, ',', ''),
                FrenchFormat::decimal($intervention->amount_cents),
                $intervention->invoice_number,
                $intervention->appointment?->reference,
            ],
        );
    }

    /**
     * Chiffres trimestriels, dans l'ordre où les demande l'état d'activité.
     */
    public function exportReport(Request $request): StreamedResponse
    {
        $this->authorize('export', Intervention::class);

        $year = $this->validatedYear($request);
        $rows = [];
        $hours = fn (int $minutes): string => number_format($minutes / 60, 2, ',', '');

        foreach ($this->sap->quarterlyBreakdown($year) as $quarter => $data) {
            $rows[] = [
                "T{$quarter} {$year}",
                $hours($data['groups'][InterventionActivity::GROUP_IT]['minutes']),
                FrenchFormat::decimal($data['groups'][InterventionActivity::GROUP_IT]['cents']),
                $data['groups'][InterventionActivity::GROUP_IT]['clients'],
                $hours($data['groups'][InterventionActivity::GROUP_ADMINISTRATIVE]['minutes']),
                FrenchFormat::decimal($data['groups'][InterventionActivity::GROUP_ADMINISTRATIVE]['cents']),
                $data['groups'][InterventionActivity::GROUP_ADMINISTRATIVE]['clients'],
                $hours($data['sap_minutes']),
                FrenchFormat::decimal($data['sap_cents']),
                $data['sap_clients'],
                $hours($data['other_minutes']),
                FrenchFormat::decimal($data['other_cents']),
            ];
        }

        return CsvExporter::streamRows(
            CsvExporter::filename("bilan-sap-{$year}"),
            [
                'Trimestre',
                'Assistance informatique — heures', 'Assistance informatique — CA (€)', 'Assistance informatique — clients',
                'Assistance administrative — heures', 'Assistance administrative — CA (€)', 'Assistance administrative — clients',
                'Total SAP — heures', 'Total SAP — CA (€)', 'Total SAP — clients particuliers',
                'Hors SAP — heures', 'Hors SAP — CA (€)',
            ],
            $rows,
        );
    }

    // -------------------------------------------------------------------------
    // Outils internes
    // -------------------------------------------------------------------------

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request): array
    {
        // « 35 », « 35,5 », « 1 234,50 € » : on accepte la saisie telle
        // qu'on l'écrit, puis on la ramène à une forme unique.
        $request->merge([
            'amount' => str_replace(
                [' ', "\u{00A0}", "\u{202F}", '€', ','],
                ['', '', '', '', '.'],
                (string) $request->input('amount'),
            ),
        ]);

        $data = $request->validate([
            'performed_on' => ['required', 'date', 'before_or_equal:today'],
            'activity' => ['required', Rule::enum(InterventionActivity::class)],
            'client_name' => ['required', 'string', 'max:150'],
            'appointment_id' => ['nullable', 'integer', 'exists:appointments,id'],
            'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'amount' => ['required', 'regex:/^\d{1,6}(\.\d{1,2})?$/'],
            'invoice_number' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'amount.regex' => __('admin.interventions.amount_invalid'),
            'performed_on.before_or_equal' => __('admin.interventions.date_in_future'),
        ], [
            'performed_on' => 'la date',
            'activity' => 'la nature de la prestation',
            'client_name' => 'le client',
            'duration_minutes' => 'la durée',
            'amount' => 'le montant',
            'invoice_number' => 'le numéro de facture',
            'notes' => 'les remarques',
        ]);

        [$euros, $decimals] = array_pad(explode('.', $data['amount']), 2, '0');
        $data['amount_cents'] = (int) $euros * 100 + (int) str_pad($decimals, 2, '0');
        unset($data['amount']);

        return $data;
    }

    protected function redirectAfterSave(Intervention $intervention, string $message): RedirectResponse
    {
        $appointment = $intervention->appointment;

        $redirect = $appointment !== null
            && ! $appointment->trashed()
            && request()->user()->can('view', $appointment)
            ? redirect()->route('admin.appointments.show', $appointment)
            : redirect()->route('admin.interventions.index', ['annee' => $intervention->performed_on->year]);

        return $redirect
            ->with('status', $message)
            ->with('status_variant', 'success');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateFilters(Request $request): array
    {
        return $request->validate([
            'annee' => ['nullable', 'integer', 'between:2000,2100'],
            'nature' => ['nullable', 'in:sap,hors_sap'],
            'recherche' => ['nullable', 'string', 'max:100'],
            'corbeille' => ['nullable', 'boolean'],
        ]);
    }

    protected function validatedYear(Request $request): int
    {
        $data = $request->validate(['annee' => ['nullable', 'integer', 'between:2000,2100']]);

        return (int) ($data['annee'] ?? now()->year);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<Intervention>
     */
    protected function filteredQuery(array $filters, int $year): Builder
    {
        return Intervention::query()
            ->inYear($year)
            ->when($filters['corbeille'] ?? false, fn (Builder $query) => $query->onlyTrashed())
            ->when($filters['nature'] ?? null, fn (Builder $query, string $nature) => $query->sap($nature === 'sap'))
            ->when($filters['recherche'] ?? null, function (Builder $query, string $terms): void {
                $query->where(function (Builder $query) use ($terms): void {
                    $query->where('reference', 'like', "%{$terms}%")
                        ->orWhere('client_name', 'like', "%{$terms}%")
                        ->orWhere('invoice_number', 'like', "%{$terms}%");
                });
            });
    }
}
