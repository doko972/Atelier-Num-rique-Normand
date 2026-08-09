<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Déclinaisons du logo.
 *
 * Une identité visuelle arrive rarement complète du premier coup : le
 * monogramme seul ou la version monochrome peuvent manquer pendant des
 * semaines. Plutôt que d'afficher une image cassée, chaque déclinaison se
 * rabat sur la version couleur complète, qui existe toujours.
 */
final class Branding
{
    /** Logo complet, en couleur. C'est la seule déclinaison obligatoire. */
    public const string FULL = 'logo.png';

    /** Monogramme seul : le N dans son cercle, sans le nom. */
    public const string MARK = 'logo-mark.png';

    /** Tout noir, pour l'impression et la photocopie. */
    public const string MONO_DARK = 'logo-noir.png';

    /** Tout blanc, pour les fonds sombres. */
    public const string MONO_LIGHT = 'logo-blanc.png';

    /** Le monogramme seul, tout blanc. */
    public const string MARK_LIGHT = 'logo-mark-blanc.png';

    /**
     * Résolutions déjà calculées, pour ne pas interroger le disque plusieurs
     * fois par requête.
     *
     * @var array<string, string>
     */
    private static array $resolved = [];

    /**
     * Adresse du logo demandé, ou de la version complète s'il n'existe pas.
     */
    public static function logo(string $variant = self::FULL): string
    {
        return asset('images/'.self::resolve($variant));
    }

    /**
     * Le fichier demandé est-il réellement présent ?
     */
    public static function has(string $variant): bool
    {
        return self::resolve($variant) === $variant;
    }

    /**
     * Icône du navigateur : le monogramme s'il existe, sinon le logo complet.
     *
     * Le format ICO n'est plus nécessaire — tous les navigateurs encore en
     * service acceptent un PNG — mais il reste utilisé s'il est fourni.
     */
    public static function favicon(): string
    {
        return self::logo(self::MARK);
    }

    public static function hasIcoFavicon(): bool
    {
        return is_file(public_path('images/favicon.ico'));
    }

    private static function resolve(string $variant): string
    {
        return self::$resolved[$variant] ??= is_file(public_path('images/'.$variant))
            ? $variant
            : self::FULL;
    }
}
