<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\HtmlString;

/**
 * Rendu des textes éditoriaux saisis depuis le back-office.
 *
 * Ces textes sont écrits par une personne, pas par un développeur : on ne
 * peut pas leur demander d'écrire du HTML, et on ne doit surtout pas leur
 * permettre d'en injecter. Tout est donc échappé d'abord, puis un jeu très
 * restreint de marques est reconnu :
 *
 *     ## Titre        → <h2>
 *     ### Sous-titre  → <h3>
 *     - élément       → <ul><li>
 *     **important**   → <strong>
 *
 * Les titres ne sont pas un agrément : ils forment la structure que les
 * lecteurs d'écran utilisent pour naviguer dans la page. Une suite de
 * paragraphes indifférenciés oblige à tout écouter pour trouver un passage.
 *
 * Aucun lien ni aucune image : une adresse tapée dans un champ de texte est
 * le vecteur d'hameçonnage le plus simple qui soit, et ce site s'adresse à
 * des personnes qu'on prévient justement contre cela.
 */
class RichText
{
    public static function render(?string $text): HtmlString
    {
        if (blank($text)) {
            return new HtmlString('');
        }

        $blocks = preg_split('/\R{2,}/', trim(str_replace("\r\n", "\n", (string) $text))) ?: [];

        return new HtmlString(
            implode("\n", array_map(self::block(...), $blocks)),
        );
    }

    /**
     * Un bloc séparé des autres par une ligne vide.
     */
    private static function block(string $block): string
    {
        $block = trim($block);

        if ($block === '') {
            return '';
        }

        if (str_starts_with($block, '### ')) {
            return '<h3>'.self::inline(mb_substr($block, 4)).'</h3>';
        }

        if (str_starts_with($block, '## ')) {
            return '<h2>'.self::inline(mb_substr($block, 3)).'</h2>';
        }

        if (str_starts_with($block, '- ')) {
            return self::list($block);
        }

        return '<p>'.nl2br(self::inline($block)).'</p>';
    }

    /**
     * Liste à puces : toutes les lignes du bloc commencent par un tiret.
     */
    private static function list(string $block): string
    {
        $items = array_map(
            static fn (string $line): string => '<li>'.self::inline(ltrim(trim($line), '- ')).'</li>',
            preg_split('/\R/', $block) ?: [],
        );

        return '<ul>'.implode('', $items).'</ul>';
    }

    /**
     * Échappement, puis mise en gras. Dans cet ordre : l'inverse laisserait
     * passer des balises écrites par la personne qui saisit le texte.
     */
    private static function inline(string $text): string
    {
        return preg_replace(
            '/\*\*(.+?)\*\*/u',
            '<strong>$1</strong>',
            e(trim($text)),
        ) ?? e(trim($text));
    }
}
