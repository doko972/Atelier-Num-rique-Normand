<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\WorkshopStatus;
use App\Models\Article;
use App\Models\Faq;
use App\Models\MissedSearch;
use App\Models\Page;
use App\Models\PracticalGuide;
use App\Models\SearchSynonym;
use App\Models\Service;
use App\Models\Workshop;
use App\Support\SearchText;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Recherche du site public.
 *
 * Pensée pour des personnes qui écrivent comme elles parlent : « mon ordi
 * est lent », « arnaque sms », « impots » sans accent. D'où trois tolérances,
 * toutes appliquées en PHP plutôt que confiées à la base, pour se comporter
 * de la même façon partout :
 *
 * - les **synonymes** du dictionnaire (« mail » trouve « courriel ») ;
 * - le **début de mot** (« ordi » trouve « ordinateur ») ;
 * - la **faute de frappe** d'une lettre sur les mots longs (« ordinatuer »).
 *
 * Le volume de contenus — quelques centaines de textes — permet de tout
 * relire à chaque recherche, sans moteur externe ni index à entretenir sur
 * un hébergement mutualisé.
 */
class SiteSearch
{
    public const int MAX_RESULTS = 12;

    /** Poids d'un mot trouvé selon l'endroit et la façon. */
    private const int IN_TITLE = 10;

    private const int PREFIX_IN_TITLE = 6;

    private const int IN_TEXT = 4;

    private const int PREFIX_IN_TEXT = 2;

    /** Poids relatif d'un mot obtenu par synonyme ou par correction. */
    private const float SYNONYM_WEIGHT = 0.8;

    private const float TYPO_WEIGHT = 0.6;

    /**
     * @return Collection<int, array{type: string, title: string, summary: string, url: string, score: float}>
     */
    public function search(string $query): Collection
    {
        $keywords = SearchText::keywords($query);

        if ($keywords === []) {
            return collect();
        }

        $documents = $this->documents();
        $concepts = $this->concepts($keywords, SearchText::normalize($query), $documents);

        $scored = $documents
            ->map(function (array $document) use ($concepts): array {
                $matched = 0;
                $score = 0.0;

                foreach ($concepts as $candidates) {
                    $best = 0.0;

                    foreach ($candidates as $word => $weight) {
                        $best = max($best, $weight * $this->scoreWord((string) $word, $document));
                    }

                    if ($best > 0) {
                        $matched++;
                        $score += $best;
                    }
                }

                return [...$document, 'matched' => $matched, 'score' => $score];
            })
            ->filter(fn (array $document): bool => $document['matched'] > 0);

        if ($scored->isEmpty()) {
            return collect();
        }

        // Un résultat qui ne couvre qu'une petite partie de la demande passe
        // après — et disparaît si d'autres la couvrent bien mieux.
        $threshold = max(1, $scored->max('matched') - 1);

        return $scored
            ->filter(fn (array $document): bool => $document['matched'] >= $threshold)
            ->sortBy([['matched', 'desc'], ['score', 'desc']])
            ->take(self::MAX_RESULTS)
            ->map(fn (array $document): array => [
                'type' => $document['type'],
                'title' => $document['title'],
                'summary' => $document['summary'],
                'url' => $document['url'],
                'score' => $document['score'],
            ])
            ->values();
    }

    /**
     * Garde la trace d'une recherche infructueuse, pour enrichir le site.
     *
     * Rien qui ressemble à une donnée personnelle n'est conservé : une
     * recherche contenant une adresse électronique ou un numéro est ignorée.
     */
    public function recordMiss(string $query): void
    {
        $normalized = Str::limit(SearchText::normalize($query), 100, '');

        if (mb_strlen($normalized) < 2 || str_contains($query, '@') || preg_match('/\d{4,}/', $query) === 1) {
            return;
        }

        MissedSearch::record($normalized);
    }

    // -------------------------------------------------------------------------
    // Correspondance
    // -------------------------------------------------------------------------

    /**
     * @param  array<string, mixed>  $document
     */
    private function scoreWord(string $word, array $document): float
    {
        if (str_contains($word, ' ')) {
            return match (true) {
                str_contains($document['title_text'], " {$word} ") => self::IN_TITLE,
                str_contains($document['text'], " {$word} ") => self::IN_TEXT,
                default => 0,
            };
        }

        if (isset($document['title_words'][$word])) {
            return self::IN_TITLE;
        }

        if (isset($document['words'][$word])) {
            return self::IN_TEXT;
        }

        // Début de mot : pas en deçà de quatre lettres, sans quoi « mot »
        // trouverait « moteur ».
        if (\strlen($word) >= 4) {
            if ($this->hasPrefix($document['title_words'], $word)) {
                return self::PREFIX_IN_TITLE;
            }

            if ($this->hasPrefix($document['words'], $word)) {
                return self::PREFIX_IN_TEXT;
            }
        }

        return 0;
    }

    /**
     * @param  array<string, true>  $words
     */
    private function hasPrefix(array $words, string $prefix): bool
    {
        foreach ($words as $candidate => $_) {
            if (str_starts_with((string) $candidate, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Chaque mot de la demande devient un « concept » : le mot lui-même,
     * ses synonymes et ses corrections probables, avec leur poids.
     *
     * @param  array<int, string>  $keywords
     * @param  Collection<int, array<string, mixed>>  $documents
     * @return array<int, array<string, float>>
     */
    private function concepts(array $keywords, string $normalizedQuery, Collection $documents): array
    {
        $groups = $this->synonymGroups();
        // Les clés numériques (« 2026 ») reviennent en entiers : on les
        // ramène en chaînes pour les comparer aux mots cherchés.
        $vocabulary = $documents
            ->flatMap(fn (array $document): array => array_map('strval', array_keys($document['words'])))
            ->unique()
            ->values()
            ->all();
        $padded = " {$normalizedQuery} ";

        $concepts = [];

        foreach ($keywords as $keyword) {
            $candidates = [$keyword => 1.0];

            foreach ($groups as $group) {
                if (in_array($keyword, $group, true)) {
                    foreach ($group as $synonym) {
                        $candidates[$synonym] ??= self::SYNONYM_WEIGHT;
                    }
                }
            }

            foreach ($this->typoCorrections($keyword, $vocabulary) as $correction) {
                $candidates[$correction] ??= self::TYPO_WEIGHT;
            }

            $concepts[] = $candidates;
        }

        // Expressions de plusieurs mots (« mot de passe », « carte vitale ») :
        // reconnues dans la demande entière, elles forment un concept à part.
        foreach ($groups as $group) {
            foreach ($group as $expression) {
                if (str_contains($expression, ' ') && str_contains($padded, " {$expression} ")) {
                    $concepts[] = array_fill_keys($group, self::SYNONYM_WEIGHT);

                    break;
                }
            }
        }

        return $concepts;
    }

    /**
     * Mots du site distants d'une lettre (deux pour les mots longs).
     *
     * @param  array<int, string>  $vocabulary
     * @return array<int, string>
     */
    private function typoCorrections(string $keyword, array $vocabulary): array
    {
        $length = \strlen($keyword);

        if ($length < 5 || in_array($keyword, $vocabulary, true)) {
            return [];
        }

        $tolerance = $length >= 8 ? 2 : 1;

        return array_values(array_filter(
            $vocabulary,
            fn (string $word): bool => abs(\strlen($word) - $length) <= $tolerance
                && levenshtein($keyword, $word) <= $tolerance,
        ));
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function synonymGroups(): array
    {
        return Cache::rememberForever(SearchSynonym::CACHE_KEY, fn (): array => SearchSynonym::query()
            ->get()
            ->map(fn (SearchSynonym $synonym): array => array_values(array_unique(array_filter(
                array_map(SearchText::normalize(...), $synonym->allWords()),
            ))))
            ->filter(fn (array $group): bool => count($group) > 1)
            ->values()
            ->all());
    }

    // -------------------------------------------------------------------------
    // Contenus cherchés
    // -------------------------------------------------------------------------

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function documents(): Collection
    {
        $documents = collect();

        foreach (__('site.search.shortcuts') as $shortcut) {
            $documents->push($this->document(
                'page',
                $shortcut['title'],
                $shortcut['summary'],
                route($shortcut['route']),
                $shortcut['keywords'],
            ));
        }

        Service::query()
            ->published()
            ->whereHas('category', fn ($query) => $query->published())
            ->with('category')
            ->get()
            ->each(fn (Service $service) => $documents->push($this->document(
                'service',
                $service->title,
                $service->summary,
                route('services.detail', [$service->category, $service]),
                $service->category->name,
                $service->description,
                implode(' ', $service->learning_points ?? []),
            )));

        PracticalGuide::query()
            ->published()
            ->with('steps')
            ->get()
            ->each(fn (PracticalGuide $guide) => $documents->push($this->document(
                'guide',
                $guide->title,
                $guide->summary,
                route('resources.guide', $guide),
                $guide->introduction,
                $guide->conclusion,
                $guide->steps->map(fn ($step): string => "{$step->title} {$step->body} {$step->tip}")->implode(' '),
            )));

        Article::query()
            ->published()
            ->get()
            ->each(fn (Article $article) => $documents->push($this->document(
                'article',
                $article->title,
                $article->excerpt,
                route('resources.article', $article),
                $article->body,
            )));

        Faq::query()
            ->published()
            ->get()
            ->each(fn (Faq $faq) => $documents->push($this->document(
                'faq',
                $faq->question,
                Str::limit(SearchText::plain($faq->answer), 200),
                route('faq'),
                $faq->answer,
            )));

        Workshop::query()
            ->whereIn('status', [WorkshopStatus::Published, WorkshopStatus::Full])
            ->upcoming()
            ->get()
            ->each(fn (Workshop $workshop) => $documents->push($this->document(
                'workshop',
                $workshop->title,
                Str::limit(SearchText::plain($workshop->description), 200),
                route('workshops.show', $workshop),
                $workshop->description,
                implode(' ', $workshop->objectives ?? []),
            )));

        $pageRoutes = [
            Page::KEY_ABOUT => 'about',
            Page::KEY_ONLINE_PROCEDURES => 'procedures',
            Page::KEY_SECURITY => 'security',
        ];

        Page::query()
            ->published()
            ->where(fn ($query) => $query->whereNull('key')->orWhereIn('key', array_keys($pageRoutes)))
            ->get()
            ->each(fn (Page $page) => $documents->push($this->document(
                'page',
                $page->title,
                $page->summary ?? Str::limit(SearchText::plain($page->body), 200),
                $page->key === null ? route('pages.show', $page) : route($pageRoutes[$page->key]),
                $page->body,
            )));

        // Une même adresse ne doit apparaître qu'une fois : la page « Sécurité
        // et arnaques » est à la fois un raccourci et une page éditable.
        return $documents->unique('url')->values();
    }

    /**
     * @return array<string, mixed>
     */
    private function document(string $type, string $title, ?string $summary, string $url, ?string ...$texts): array
    {
        $titleText = SearchText::normalize($title);
        $text = SearchText::normalize(implode(' ', [$title, $summary, ...$texts]));

        return [
            'type' => $type,
            'title' => $title,
            'summary' => (string) $summary,
            'url' => $url,
            'title_text' => " {$titleText} ",
            'text' => " {$text} ",
            'title_words' => array_fill_keys(array_filter(explode(' ', $titleText)), true),
            'words' => array_fill_keys(array_filter(explode(' ', $text)), true),
        ];
    }
}
