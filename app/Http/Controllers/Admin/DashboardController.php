<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\AuditLog;
use App\Models\ContactRequest;
use App\Models\DataDeletionRequest;
use App\Models\DataExportRequest;
use App\Models\PracticalGuide;
use App\Models\WorkshopRegistration;
use App\Services\StatisticsService;
use Illuminate\Contracts\View\View;

/**
 * Tableau de bord du back-office (codex §24).
 */
class DashboardController extends Controller
{
    public function __invoke(StatisticsService $statistics): View
    {
        $user = request()->user();

        return view('admin.dashboard', [
            'counters' => $statistics->dashboardCounters(),
            'fillRate' => $statistics->averageFillRate(),
            'cancellationRate' => $statistics->cancellationRate(),
            'monthly' => $statistics->appointmentsPerMonth(),

            'upcomingWorkshops' => $statistics->upcomingWorkshopsWithSeats(),

            'latestAppointments' => Appointment::query()
                ->needsAttention()
                ->with('municipality')
                ->latest()
                ->limit(8)
                ->get(),

            'overdueCallbacks' => Appointment::query()
                ->open()
                ->whereDate('callback_on', '<=', today())
                ->with('assignee')
                ->orderBy('callback_on')
                ->limit(5)
                ->get(),

            'latestRegistrations' => WorkshopRegistration::query()
                ->with('workshop')
                ->latest()
                ->limit(6)
                ->get(),

            'unreadMessages' => ContactRequest::query()
                ->open()
                ->latest()
                ->limit(5)
                ->get(),

            // Alertes techniques et de conformité.
            'guidesNeedingReview' => PracticalGuide::query()
                ->published()
                ->where(function ($query): void {
                    $query->whereNull('reviewed_on')
                        ->orWhere('reviewed_on', '<', now()->subYear());
                })
                ->limit(5)
                ->get(),

            'overdueGdpr' => $user->hasPermission(Permission::ManageGdprRequests)
                ? DataExportRequest::query()->open()->whereDate('due_on', '<', today())->count()
                    + DataDeletionRequest::query()->open()->whereDate('due_on', '<', today())->count()
                : 0,

            'recentActivity' => $user->hasPermission(Permission::ViewAuditLog)
                ? AuditLog::query()->with('user')->recent()->limit(10)->get()
                : collect(),
        ]);
    }
}
