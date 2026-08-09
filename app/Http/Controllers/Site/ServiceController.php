<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Contracts\View\View;

/**
 * Catalogue des services d'accompagnement (codex §9).
 */
class ServiceController extends Controller
{
    public function index(): View
    {
        return view('site.services.index', [
            'categories' => ServiceCategory::query()
                ->published()
                ->with(['services' => fn ($query) => $query->published()->ordered()])
                ->ordered()
                ->get(),
        ]);
    }

    public function show(ServiceCategory $category): View
    {
        abort_unless($category->status->isPublic(), 404);

        return view('site.services.show', [
            'category' => $category,
            'services' => $category->services()->published()->ordered()->get(),
            'otherCategories' => ServiceCategory::query()
                ->published()
                ->whereKeyNot($category->getKey())
                ->ordered()
                ->get(),
        ]);
    }

    public function detail(ServiceCategory $category, Service $service): View
    {
        abort_unless($category->status->isPublic() && $service->status->isPublic(), 404);
        abort_unless($service->service_category_id === $category->getKey(), 404);

        return view('site.services.detail', [
            'category' => $category,
            'service' => $service,
            'siblings' => $category->services()
                ->published()
                ->whereKeyNot($service->getKey())
                ->ordered()
                ->limit(4)
                ->get(),
        ]);
    }
}
