<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Municipality;
use App\Models\Partner;
use App\Models\Service;
use App\Models\Testimonial;
use App\Models\Workshop;
use Illuminate\Contracts\View\View;

/**
 * Page d'accueil (codex §8).
 */
class HomeController extends Controller
{
    public function __invoke(): View
    {
        return view('site.home', [
            'services' => Service::query()
                ->published()
                ->featured()
                ->with('category')
                ->ordered()
                ->limit(8)
                ->get(),

            'workshops' => Workshop::query()
                ->public()
                ->upcoming()
                ->with(['location', 'municipality', 'category'])
                ->withCount([
                    'registrations as active_registrations_count' => fn ($query) => $query->occupyingSeat(),
                ])
                ->limit((int) config('site.workshops.home_limit'))
                ->get(),

            'testimonials' => Testimonial::query()
                ->published()
                ->with('municipality')
                ->ordered()
                ->limit(3)
                ->get(),

            'partners' => Partner::query()
                ->published()
                ->ordered()
                ->limit(12)
                ->get(),

            'municipalities' => Municipality::query()
                ->covered()
                ->ordered()
                ->get(),
        ]);
    }
}
