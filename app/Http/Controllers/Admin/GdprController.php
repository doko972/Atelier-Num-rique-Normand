<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\DataRequestStatus;
use App\Enums\DataRequestType;
use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\DataDeletionRequest;
use App\Models\DataExportRequest;
use App\Services\GdprService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Traitement des demandes RGPD (codex §27).
 *
 * Deux règles structurent cet écran : rien n'est communiqué ni effacé avant
 * vérification de l'identité, et le délai légal d'un mois est affiché en
 * permanence.
 */
class GdprController extends Controller
{
    public function __construct(
        protected GdprService $gdpr,
    ) {}

    public function index(): View
    {
        $this->authorize(Permission::ManageGdprRequests->value);

        return view('admin.gdpr.index', [
            'exportRequests' => DataExportRequest::query()
                ->with('handler')
                ->orderByDesc('created_at')
                ->paginate(15, pageName: 'exports'),
            'deletionRequests' => DataDeletionRequest::query()
                ->with('handler')
                ->orderByDesc('created_at')
                ->paginate(15, pageName: 'suppressions'),
            'types' => DataRequestType::options(),
            'statuses' => DataRequestStatus::options(),
            'scopes' => DataDeletionRequest::scopeOptions(),
        ]);
    }

    /**
     * Enregistre une demande reçue par courrier, par téléphone ou par courriel.
     */
    public function storeExport(Request $request): RedirectResponse
    {
        $this->authorize(Permission::ManageGdprRequests->value);

        $data = $request->validate([
            'type' => ['required', Rule::enum(DataRequestType::class)],
            'requester_name' => ['required', 'string', 'max:150'],
            'requester_email' => ['nullable', 'email', 'max:180'],
            'requester_phone' => ['nullable', 'string', 'max:30'],
            'details' => ['nullable', 'string', 'max:2000'],
        ], attributes: __('rgpd.attributes'));

        $exportRequest = DataExportRequest::create($data);

        Log::channel('rgpd')->notice('Demande RGPD enregistrée.', [
            'reference' => $exportRequest->reference,
            'type' => $exportRequest->type->value,
        ]);

        return back()
            ->with('status', __('rgpd.request_recorded', ['reference' => $exportRequest->reference]))
            ->with('status_variant', 'success');
    }

    public function storeDeletion(Request $request): RedirectResponse
    {
        $this->authorize(Permission::ManageGdprRequests->value);

        $data = $request->validate([
            'requester_name' => ['required', 'string', 'max:150'],
            'requester_email' => ['nullable', 'email', 'max:180'],
            'requester_phone' => ['nullable', 'string', 'max:30'],
            'scope' => ['required', Rule::in(array_keys(DataDeletionRequest::scopeOptions()))],
            'details' => ['nullable', 'string', 'max:2000'],
        ], attributes: __('rgpd.attributes'));

        if (blank($data['requester_email']) && blank($data['requester_phone'])) {
            return back()
                ->withInput()
                ->withErrors(['requester_email' => __('rgpd.identifier_required')]);
        }

        $deletionRequest = DataDeletionRequest::create($data);

        Log::channel('rgpd')->notice('Demande d’effacement enregistrée.', [
            'reference' => $deletionRequest->reference,
        ]);

        return back()
            ->with('status', __('rgpd.request_recorded', ['reference' => $deletionRequest->reference]))
            ->with('status_variant', 'success');
    }

    /**
     * Prévisualise les données détenues, avant toute communication.
     */
    public function previewExport(DataExportRequest $exportRequest): JsonResponse
    {
        $this->authorize(Permission::ManageGdprRequests->value);

        abort_unless(
            $exportRequest->identity_verified,
            403,
            __('rgpd.identity_check_required'),
        );

        Log::channel('rgpd')->info('Consultation d’un export RGPD.', [
            'reference' => $exportRequest->reference,
        ]);

        return response()->json(
            $this->gdpr->buildExport(
                $exportRequest->requester_email,
                $exportRequest->requester_phone,
            ),
            options: JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE,
        );
    }

    public function verifyIdentity(Request $request, string $type, int $id): RedirectResponse
    {
        $this->authorize(Permission::ManageGdprRequests->value);

        $model = $this->resolve($type, $id);

        $model->update([
            'identity_verified' => true,
            'identity_verified_at' => now(),
            'status' => DataRequestStatus::InProgress,
            'handled_by' => $request->user()->getKey(),
        ]);

        Log::channel('rgpd')->notice('Identité vérifiée.', ['reference' => $model->reference]);

        return back()
            ->with('status', __('rgpd.identity_verified'))
            ->with('status_variant', 'success');
    }

    /**
     * Exécute une demande d'effacement : les enregistrements sont anonymisés.
     */
    public function execute(DataDeletionRequest $deletionRequest): RedirectResponse
    {
        $this->authorize(Permission::ManageGdprRequests->value);

        abort_unless(
            $deletionRequest->identity_verified,
            403,
            __('rgpd.identity_check_required'),
        );

        $count = $this->gdpr->anonymise($deletionRequest);

        $deletionRequest->update([
            'records_anonymised' => $count,
            'status' => DataRequestStatus::Completed,
            'completed_at' => now(),
        ]);

        return back()
            ->with('status', __('rgpd.deletion_done', ['count' => $count]))
            ->with('status_variant', 'success');
    }

    public function updateStatus(Request $request, string $type, int $id): RedirectResponse
    {
        $this->authorize(Permission::ManageGdprRequests->value);

        $data = $request->validate([
            'status' => ['required', Rule::enum(DataRequestStatus::class)],
            'internal_notes' => ['nullable', 'string', 'max:2000'],
            'refusal_reason' => ['nullable', 'string', 'max:1000'],
        ], attributes: __('rgpd.attributes'));

        $model = $this->resolve($type, $id);
        $status = DataRequestStatus::from($data['status']);

        $attributes = [
            'status' => $status,
            'internal_notes' => $data['internal_notes'] ?? null,
        ];

        if ($status === DataRequestStatus::Completed) {
            $attributes['completed_at'] = now();
        }

        if ($model instanceof DataDeletionRequest) {
            $attributes['refusal_reason'] = $data['refusal_reason'] ?? null;
        }

        $model->update($attributes);

        return back()
            ->with('status', __('rgpd.status_updated'))
            ->with('status_variant', 'success');
    }

    protected function resolve(string $type, int $id): DataExportRequest|DataDeletionRequest
    {
        return match ($type) {
            'export' => DataExportRequest::findOrFail($id),
            'suppression' => DataDeletionRequest::findOrFail($id),
            default => abort(404),
        };
    }
}
