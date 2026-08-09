<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StorePartnershipRequest as PartnershipForm;
use App\Models\PartnershipRequest;
use App\Models\User;
use App\Support\CsvExporter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Demandes de partenariat émanant des collectivités et associations.
 */
class PartnershipRequestController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', PartnershipRequest::class);

        $filters = $request->validate([
            'statut' => ['nullable', Rule::enum(RequestStatus::class)],
            'recherche' => ['nullable', 'string', 'max:100'],
        ]);

        return view('admin.partnership-requests.index', [
            'requests' => $this->filteredQuery($filters)
                ->with(['municipality', 'assignee'])
                ->latest()
                ->paginate((int) config('site.per_page.admin'))
                ->withQueryString(),
            'filters' => $filters,
            'statuses' => RequestStatus::partnershipCases(),
        ]);
    }

    public function show(PartnershipRequest $partnershipRequest): View
    {
        $this->authorize('view', $partnershipRequest);

        return view('admin.partnership-requests.show', [
            'partnershipRequest' => $partnershipRequest->load(['municipality', 'assignee', 'consentLogs']),
            'statuses' => RequestStatus::partnershipCases(),
            'advisers' => User::query()->advisers()->orderBy('name')->get(),
            'needLabels' => PartnershipForm::needOptions(),
        ]);
    }

    public function update(Request $request, PartnershipRequest $partnershipRequest): RedirectResponse
    {
        $this->authorize('update', $partnershipRequest);

        $data = $request->validate([
            'status' => ['required', Rule::enum(RequestStatus::class)],
            'assigned_to' => ['nullable', 'integer', 'exists:users,id'],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
        ], attributes: [
            'status' => 'le statut',
            'assigned_to' => 'le conseiller',
            'internal_notes' => 'les notes internes',
        ]);

        $status = RequestStatus::from($data['status']);

        $partnershipRequest->fill([
            'status' => $status,
            'assigned_to' => $data['assigned_to'] ?? null,
            'internal_notes' => $data['internal_notes'] ?? null,
        ]);

        if (! $status->isOpen() && $partnershipRequest->closed_at === null) {
            $partnershipRequest->closed_at = now();
        }

        $partnershipRequest->save();

        return back()
            ->with('status', __('admin.requests.updated'))
            ->with('status_variant', 'success');
    }

    public function anonymise(PartnershipRequest $partnershipRequest): RedirectResponse
    {
        $this->authorize('anonymise', $partnershipRequest);

        $partnershipRequest->anonymise();

        return back()
            ->with('status', __('admin.requests.anonymised'))
            ->with('status_variant', 'success');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', PartnershipRequest::class);

        $filters = $request->validate([
            'statut' => ['nullable', Rule::enum(RequestStatus::class)],
            'recherche' => ['nullable', 'string', 'max:100'],
        ]);

        $needLabels = PartnershipForm::needOptions();

        return CsvExporter::stream(
            CsvExporter::filename('demandes-de-partenariat'),
            [
                'Référence', 'Reçue le', 'Statut', 'Structure', 'Type', 'Contact',
                'Fonction', 'Courriel', 'Téléphone', 'Commune', 'Besoins',
                'Participants estimés', 'Période souhaitée', 'Devis demandé',
            ],
            $this->filteredQuery($filters)->with('municipality'),
            fn (PartnershipRequest $partnership): array => [
                $partnership->reference,
                $partnership->created_at?->format('d/m/Y H:i'),
                $partnership->status->label(),
                $partnership->organisation_name,
                $partnership->organisation_type->label(),
                $partnership->contact_name,
                $partnership->contact_role,
                $partnership->email,
                $partnership->phone,
                $partnership->municipality?->name ?? $partnership->municipality_name,
                collect($partnership->needs ?? [])
                    ->map(fn (string $need): string => $needLabels[$need] ?? $need)
                    ->implode(' / '),
                $partnership->estimated_participants,
                $partnership->desired_period,
                $partnership->quote_requested,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<PartnershipRequest>
     */
    protected function filteredQuery(array $filters): Builder
    {
        return PartnershipRequest::query()
            ->when($filters['statut'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['recherche'] ?? null, function ($query, string $terms): void {
                $query->where(function ($query) use ($terms): void {
                    $query->where('reference', 'like', "%{$terms}%")
                        ->orWhere('organisation_name', 'like', "%{$terms}%")
                        ->orWhere('contact_name', 'like', "%{$terms}%");
                });
            });
    }
}
