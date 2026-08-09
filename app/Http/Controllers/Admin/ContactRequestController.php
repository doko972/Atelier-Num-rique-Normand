<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\ContactRequest;
use App\Models\User;
use App\Support\CsvExporter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Messages reçus depuis le formulaire de contact.
 */
class ContactRequestController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', ContactRequest::class);

        $filters = $request->validate([
            'statut' => ['nullable', Rule::enum(RequestStatus::class)],
            'recherche' => ['nullable', 'string', 'max:100'],
        ]);

        return view('admin.contact-requests.index', [
            'requests' => $this->filteredQuery($filters)
                ->with(['municipality', 'assignee'])
                ->latest()
                ->paginate((int) config('site.per_page.admin'))
                ->withQueryString(),
            'filters' => $filters,
            'statuses' => RequestStatus::contactCases(),
        ]);
    }

    public function show(ContactRequest $contactRequest): View
    {
        $this->authorize('view', $contactRequest);

        return view('admin.contact-requests.show', [
            'contactRequest' => $contactRequest->load(['municipality', 'assignee', 'consentLogs']),
            'statuses' => RequestStatus::contactCases(),
            'advisers' => User::query()->advisers()->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, ContactRequest $contactRequest): RedirectResponse
    {
        $this->authorize('update', $contactRequest);

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

        $contactRequest->fill([
            'status' => $status,
            'assigned_to' => $data['assigned_to'] ?? null,
            'internal_notes' => $data['internal_notes'] ?? null,
        ]);

        if ($status === RequestStatus::Answered && $contactRequest->answered_at === null) {
            $contactRequest->answered_at = now();
        }

        if (! $status->isOpen() && $contactRequest->closed_at === null) {
            $contactRequest->closed_at = now();
        }

        $contactRequest->save();

        return back()
            ->with('status', __('admin.requests.updated'))
            ->with('status_variant', 'success');
    }

    public function anonymise(ContactRequest $contactRequest): RedirectResponse
    {
        $this->authorize('anonymise', $contactRequest);

        $contactRequest->anonymise();

        return back()
            ->with('status', __('admin.requests.anonymised'))
            ->with('status_variant', 'success');
    }

    public function export(Request $request): StreamedResponse
    {
        $this->authorize('export', ContactRequest::class);

        $filters = $request->validate([
            'statut' => ['nullable', Rule::enum(RequestStatus::class)],
            'recherche' => ['nullable', 'string', 'max:100'],
        ]);

        return CsvExporter::stream(
            CsvExporter::filename('messages-recus'),
            ['Référence', 'Reçu le', 'Statut', 'Prénom', 'Nom', 'Téléphone', 'Courriel', 'Commune', 'Sujet', 'Message'],
            $this->filteredQuery($filters)->with('municipality'),
            fn (ContactRequest $contact): array => [
                $contact->reference,
                $contact->created_at?->format('d/m/Y H:i'),
                $contact->status->label(),
                $contact->first_name,
                $contact->last_name,
                $contact->phone,
                $contact->email,
                $contact->municipality?->name,
                $contact->subject,
                $contact->message,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<ContactRequest>
     */
    protected function filteredQuery(array $filters): Builder
    {
        return ContactRequest::query()
            ->when($filters['statut'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['recherche'] ?? null, function ($query, string $terms): void {
                $query->where(function ($query) use ($terms): void {
                    $query->where('reference', 'like', "%{$terms}%")
                        ->orWhere('subject', 'like', "%{$terms}%")
                        ->orWhere('last_name', 'like', "%{$terms}%")
                        ->orWhere('first_name', 'like', "%{$terms}%");
                });
            });
    }
}
