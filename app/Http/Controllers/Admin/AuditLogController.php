<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Permission;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Journal des actions d'administration (codex §24).
 *
 * Le journal est en lecture seule : aucune interface ne permet d'en modifier
 * ou d'en supprimer une entrée.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize(Permission::ViewAuditLog->value);

        $filters = $request->validate([
            'action' => ['nullable', 'string', 'max:60'],
            'utilisateur' => ['nullable', 'integer', 'exists:users,id'],
            'canal' => ['nullable', 'string', 'max:40'],
        ]);

        return view('admin.audit-logs.index', [
            'logs' => AuditLog::query()
                ->with('user')
                ->when($filters['action'] ?? null, fn ($query, string $action) => $query->where('action', $action))
                ->when($filters['utilisateur'] ?? null, fn ($query, int $id) => $query->where('user_id', $id))
                ->when($filters['canal'] ?? null, fn ($query, string $channel) => $query->where('channel', $channel))
                ->recent()
                ->paginate((int) config('site.per_page.admin'))
                ->withQueryString(),
            'filters' => $filters,
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
            'channels' => AuditLog::query()->distinct()->orderBy('channel')->pluck('channel'),
            'users' => User::query()->withTrashed()->orderBy('name')->get(),
        ]);
    }
}
