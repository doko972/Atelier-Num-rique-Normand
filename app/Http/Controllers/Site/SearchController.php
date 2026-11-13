<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Services\SiteSearch;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Recherche dans tout le site public.
 */
class SearchController extends Controller
{
    public function __invoke(Request $request, SiteSearch $search): View
    {
        // Une recherche trop longue n'est pas une erreur de la personne :
        // on la tronque plutôt que de lui opposer un message.
        $query = mb_substr(trim((string) $request->query('q', '')), 0, 150);

        $results = $query === '' ? collect() : $search->search($query);

        if ($query !== '' && $results->isEmpty()) {
            $search->recordMiss($query);
        }

        return view('site.search', [
            'query' => $query,
            'results' => $results,
        ]);
    }
}
