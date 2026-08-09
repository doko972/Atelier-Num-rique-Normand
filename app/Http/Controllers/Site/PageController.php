<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\AccessibilityReport;
use App\Models\Faq;
use App\Models\OfficialLink;
use App\Models\Page;
use App\Models\Pricing;
use App\Models\Testimonial;
use Illuminate\Contracts\View\View;

/**
 * Pages éditoriales, tarifs, questions fréquentes et pages légales.
 */
class PageController extends Controller
{
    /**
     * Page éditoriale libre, retrouvée par son identifiant d'URL.
     */
    public function show(Page $page): View
    {
        abort_unless($page->status->isPublic(), 404);

        return view('site.pages.show', ['page' => $page]);
    }

    /**
     * Page système, retrouvée par sa clé stable.
     */
    public function system(string $key): View
    {
        $page = Page::findByKey($key);

        abort_if($page === null || ! $page->status->isPublic(), 404);

        return view('site.pages.show', ['page' => $page]);
    }

    public function about(): View
    {
        $page = Page::findByKey(Page::KEY_ABOUT);

        abort_if($page === null || ! $page->status->isPublic(), 404);

        return view('site.pages.about', [
            'page' => $page,
            // La commune est utilisée dans la signature du témoignage :
            // elle est chargée d'emblée pour éviter une requête par ligne.
            'testimonials' => Testimonial::query()
                ->published()
                ->with('municipality')
                ->ordered()
                ->limit(4)
                ->get(),
        ]);
    }

    /**
     * Page « Démarches en ligne » : cadre de l'accompagnement et limites.
     */
    public function onlineProcedures(): View
    {
        $page = Page::findByKey(Page::KEY_ONLINE_PROCEDURES);

        abort_if($page === null || ! $page->status->isPublic(), 404);

        return view('site.pages.procedures', [
            'page' => $page,
            'links' => OfficialLink::query()
                ->published()
                ->where('category', OfficialLink::CATEGORY_PROCEDURES)
                ->ordered()
                ->get(),
        ]);
    }

    /**
     * Page « Sécurité et arnaques ».
     */
    public function security(): View
    {
        $page = Page::findByKey(Page::KEY_SECURITY);

        abort_if($page === null || ! $page->status->isPublic(), 404);

        return view('site.pages.security', [
            'page' => $page,
            'links' => OfficialLink::query()
                ->published()
                ->where('category', OfficialLink::CATEGORY_SECURITY)
                ->ordered()
                ->get(),
            'faqs' => Faq::query()
                ->published()
                ->where('category', 'securite')
                ->ordered()
                ->get(),
        ]);
    }

    public function pricing(): View
    {
        return view('site.pages.pricing', [
            'pricings' => Pricing::query()->published()->ordered()->get(),
        ]);
    }

    public function faq(): View
    {
        return view('site.pages.faq', [
            'groups' => Faq::query()
                ->published()
                ->ordered()
                ->get()
                ->groupBy('category'),
        ]);
    }

    /**
     * Déclaration d'accessibilité, complétée par le dernier audit publié.
     */
    public function accessibility(): View
    {
        $page = Page::findByKey(Page::KEY_ACCESSIBILITY);

        abort_if($page === null || ! $page->status->isPublic(), 404);

        return view('site.pages.accessibility', [
            'page' => $page,
            'report' => AccessibilityReport::query()->published()->first(),
        ]);
    }
}
