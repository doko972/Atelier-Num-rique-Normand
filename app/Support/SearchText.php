<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Mise en forme commune des textes cherchés et des textes indexés.
 *
 * Les deux côtés passent par les mêmes règles, sans quoi « Impôts » ne
 * trouverait jamais « impots » : minuscules, accents retirés, ponctuation
 * remplacée par des espaces, mots vides écartés, pluriel simple ramené au
 * singulier.
 */
final class SearchText
{
    /**
     * Mots qui ne disent rien du sujet cherché.
     *
     * « aide », « besoin », « comment » figurent ici : presque toutes les
     * demandes les contiennent, aucune ne se distingue par eux.
     *
     * @var array<int, string>
     */
    private const array STOPWORDS = [
        'a', 'ai', 'aide', 'aider', 'as', 'au', 'aux', 'avec', 'avoir', 'besoin',
        'bonjour', 'c', 'ca', 'ce', 'cela', 'ces', 'cet', 'cette', 'comment', 'd',
        'dans', 'de', 'des', 'du', 'elle', 'en', 'est', 'et', 'etre', 'fait',
        'faire', 'il', 'ils', 'j', 'je', 'l', 'la', 'le', 'les', 'm', 'ma', 'me',
        'merci', 'mes', 'mon', 'n', 'ne', 'nous', 'on', 'ou', 'par', 'pas', 'peux',
        'plus', 'pour', 'pourquoi', 'puis', 'qu', 'quand', 'que', 'quel', 'quelle',
        'qui', 'quoi', 's', 'sa', 'sans', 'se', 'ses', 'son', 'sont', 'suis', 'sur',
        'svp', 't', 'ta', 'tes', 'ton', 'tres', 'trop', 'tu', 'un', 'une', 'veux',
        'vos', 'votre', 'voudrais', 'vous', 'y',
    ];

    /**
     * Texte réduit à des mots en minuscules, sans accents ni ponctuation.
     */
    public static function normalize(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = Str::ascii(mb_strtolower($text));
        $text = (string) preg_replace('/[^a-z0-9]+/', ' ', $text);

        $words = array_map(self::stem(...), explode(' ', trim($text)));

        return implode(' ', array_filter($words, fn (string $word): bool => $word !== ''));
    }

    /**
     * Texte lisible pour un extrait : sans balises ni marques de mise en
     * forme (`##`, `**`, `- `), sur une seule ligne.
     */
    public static function plain(?string $text): string
    {
        $text = strip_tags((string) $text);
        $text = (string) preg_replace('/^\s*(#{2,3}|-)\s+/m', '', $text);
        $text = str_replace('**', '', $text);

        return Str::squish($text);
    }

    /**
     * Mots porteurs de sens, dans l'ordre et sans doublon.
     *
     * Une requête faite uniquement de mots vides (« aide ») les garde tous :
     * mieux vaut une recherche large qu'une recherche vide.
     *
     * @return array<int, string>
     */
    public static function keywords(string $text): array
    {
        $words = array_values(array_unique(array_filter(explode(' ', self::normalize($text)))));

        $meaningful = array_values(array_filter(
            $words,
            fn (string $word): bool => ! in_array($word, self::STOPWORDS, true),
        ));

        return $meaningful === [] ? $words : $meaningful;
    }

    /**
     * Pluriel courant ramené au singulier : « photos » → « photo »,
     * « impots » → « impot ». Rudimentaire, mais appliqué des deux côtés.
     */
    private static function stem(string $word): string
    {
        if (\strlen($word) > 4 && in_array($word[-1], ['s', 'x'], true)) {
            return substr($word, 0, -1);
        }

        return $word;
    }
}
