<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Enums\AppointmentType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreAppointmentRequest;
use App\Models\Municipality;
use App\Services\AppointmentService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Demande de rendez-vous (codex §11).
 */
class AppointmentController extends Controller
{
    public function __construct(
        protected AppointmentService $appointments,
    ) {}

    public function create(): View
    {
        return view('site.appointments.create', [
            'municipalities' => Municipality::query()->covered()->ordered()->get(),
            'types' => AppointmentType::cases(),
        ]);
    }

    public function store(StoreAppointmentRequest $request): RedirectResponse
    {
        $appointment = $this->appointments->createFromPublicForm($request->appointmentData());

        // La référence transite par la session : elle n'apparaît jamais dans
        // l'URL, qui pourrait être partagée ou conservée dans l'historique.
        return redirect()
            ->route('appointments.confirmation')
            ->with('appointment_reference', $appointment->reference)
            ->with('appointment_has_email', $appointment->canReceiveEmail());
    }

    public function confirmation(): View|RedirectResponse
    {
        if (! session()->has('appointment_reference')) {
            return redirect()->route('appointments.create');
        }

        return view('site.appointments.confirmation', [
            'reference' => session('appointment_reference'),
            'hasEmail' => (bool) session('appointment_has_email'),
        ]);
    }
}
