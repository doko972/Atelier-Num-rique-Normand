<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MissedSearch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Recherches du site public restées sans réponse.
 *
 * Les mots que les visiteurs emploient réellement : de quoi compléter le
 * dictionnaire de synonymes, ou repérer une fiche pratique qui manque.
 */
class MissedSearchController extends Controller
{
    public function index(): View
    {
        return view('admin.missed-searches.index', [
            'searches' => MissedSearch::query()
                ->orderByDesc('hits')
                ->orderByDesc('last_searched_at')
                ->paginate((int) config('site.per_page.admin')),
        ]);
    }

    /**
     * Retire une recherche traitée, ou sans intérêt.
     */
    public function destroy(MissedSearch $missedSearch): RedirectResponse
    {
        $missedSearch->delete();

        return back()
            ->with('status', __('admin.missed_searches.deleted'))
            ->with('status_variant', 'success');
    }

    public function clear(): RedirectResponse
    {
        MissedSearch::query()->delete();

        return redirect()
            ->route('admin.missed-searches.index')
            ->with('status', __('admin.missed_searches.cleared'))
            ->with('status_variant', 'success');
    }
}
