<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\WorkshopStatus;
use App\Models\Article;
use App\Models\Faq;
use App\Models\Municipality;
use App\Models\PracticalGuide;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Workshop;
use App\Services\SettingsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Données structurées Schema.org (codex §29).
 *
 * Ces tableaux sont construits en PHP, et non directement dans les vues :
 * Blade compile `@context` comme une directive — celle introduite par Laravel
 * pour la fonctionnalité Context — et corrompait silencieusement chaque bloc
 * JSON-LD écrit dans un fichier `.blade.php`.
 */
final class StructuredData
{
    private const string CONTEXT = 'https://schema.org';

    /**
     * Le service lui-même, pour la page d'accueil.
     *
     * @param  Collection<int, Municipality>  $municipalities
     * @return array<string, mixed>
     */
    public static function professionalService(SettingsService $settings, Collection $municipalities): array
    {
        return array_filter([
            '@context' => self::CONTEXT,
            '@type' => 'ProfessionalService',
            'name' => $settings->string('site_name'),
            'description' => $settings->string('site_tagline'),
            'url' => route('home'),
            'telephone' => $settings->string('phone') ?: null,
            'email' => $settings->string('email') ?: null,
            'areaServed' => $municipalities
                ->map(fn ($municipality): array => [
                    '@type' => 'City',
                    'name' => $municipality->name,
                ])
                ->values()
                ->all() ?: null,
            'address' => $settings->string('city') ? array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => $settings->string('address') ?: null,
                'postalCode' => $settings->string('postal_code') ?: null,
                'addressLocality' => $settings->string('city'),
                'addressCountry' => 'FR',
            ]) : null,
        ]);
    }

    /**
     * Le conseiller, pour la page « À propos ».
     *
     * @return array<string, mixed>|null
     */
    public static function person(SettingsService $settings): ?array
    {
        $name = $settings->string('adviser_name');

        if (blank($name)) {
            return null;
        }

        return array_filter([
            '@context' => self::CONTEXT,
            '@type' => 'Person',
            'name' => $name,
            'jobTitle' => 'Conseiller numérique',
            'telephone' => $settings->string('phone') ?: null,
            'url' => route('about'),
        ]);
    }

    /**
     * Un atelier, sous forme d'événement.
     *
     * @return array<string, mixed>
     */
    public static function event(Workshop $workshop): array
    {
        return array_filter([
            '@context' => self::CONTEXT,
            '@type' => 'Event',
            'name' => $workshop->title,
            'description' => Str::limit(strip_tags($workshop->description), 300),
            'startDate' => $workshop->startsAt()->toIso8601String(),
            'endDate' => $workshop->endsAt()->toIso8601String(),
            'eventStatus' => $workshop->status === WorkshopStatus::Cancelled
                ? 'https://schema.org/EventCancelled'
                : 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'url' => route('workshops.show', $workshop),
            'location' => $workshop->location ? [
                '@type' => 'Place',
                'name' => $workshop->location->name,
                'address' => array_filter([
                    '@type' => 'PostalAddress',
                    'streetAddress' => $workshop->location->address_line,
                    'postalCode' => $workshop->location->postal_code,
                    'addressLocality' => $workshop->location->city,
                    'addressCountry' => 'FR',
                ]),
            ] : null,
            'offers' => [
                '@type' => 'Offer',
                'price' => $workshop->is_free ? 0 : ($workshop->price_cents ?? 0) / 100,
                'priceCurrency' => 'EUR',
                'availability' => $workshop->remainingSeats() > 0
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/SoldOut',
                'url' => route('workshops.register', $workshop),
            ],
        ]);
    }

    /**
     * Un article de conseils.
     *
     * @return array<string, mixed>
     */
    public static function article(Article $article): array
    {
        return array_filter([
            '@context' => self::CONTEXT,
            '@type' => 'Article',
            'headline' => $article->title,
            'description' => $article->excerpt,
            'datePublished' => $article->published_at?->toIso8601String(),
            'dateModified' => $article->updated_at?->toIso8601String(),
            'inLanguage' => 'fr-FR',
            'url' => route('resources.article', $article),
        ]);
    }

    /**
     * Une fiche pratique, décrite comme un mode d'emploi.
     *
     * @return array<string, mixed>
     */
    public static function howTo(PracticalGuide $guide): array
    {
        return array_filter([
            '@context' => self::CONTEXT,
            '@type' => 'HowTo',
            'name' => $guide->title,
            'description' => $guide->summary,
            'inLanguage' => 'fr-FR',
            'url' => route('resources.guide', $guide),
            'totalTime' => $guide->estimated_minutes
                ? 'PT'.$guide->estimated_minutes.'M'
                : null,
            'step' => $guide->steps
                ->map(fn ($step): array => array_filter([
                    '@type' => 'HowToStep',
                    'position' => $step->position,
                    'name' => $step->title,
                    'text' => strip_tags($step->body),
                ]))
                ->values()
                ->all() ?: null,
        ]);
    }

    /**
     * Un service d'accompagnement.
     *
     * @return array<string, mixed>
     */
    public static function service(ServiceCategory $category, Service $service): array
    {
        return [
            '@context' => self::CONTEXT,
            '@type' => 'Service',
            'name' => $service->title,
            'description' => $service->summary,
            'serviceType' => $category->name,
            'url' => route('services.detail', [$category, $service]),
        ];
    }

    /**
     * Une liste de questions fréquentes.
     *
     * @param  Collection<int, Faq>  $faqs
     * @return array<string, mixed>|null
     */
    public static function faqPage(Collection $faqs): ?array
    {
        if ($faqs->isEmpty()) {
            return null;
        }

        return [
            '@context' => self::CONTEXT,
            '@type' => 'FAQPage',
            'mainEntity' => $faqs
                ->map(fn ($faq): array => [
                    '@type' => 'Question',
                    'name' => $faq->question,
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => strip_tags($faq->answer),
                    ],
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Un fil d'Ariane.
     *
     * @param  array<string, string|null>  $items  libellé => adresse
     * @return array<string, mixed>
     */
    public static function breadcrumb(array $items): array
    {
        $elements = [];
        $position = 1;

        foreach ($items as $label => $url) {
            $elements[] = array_filter([
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $label,
                'item' => $url,
            ]);
        }

        return [
            '@context' => self::CONTEXT,
            '@type' => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }

    /**
     * Sérialise un bloc pour insertion dans une balise `script`.
     *
     * @param  array<string, mixed>  $data
     */
    public static function toJson(array $data): string
    {
        return (string) json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
    }
}
