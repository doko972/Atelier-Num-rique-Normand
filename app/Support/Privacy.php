<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Config;

/**
 * Outils de minimisation des données.
 *
 * Aucune adresse IP n'est conservée en clair : seul un condensé salé permet de
 * repérer des envois répétés depuis une même origine, sans jamais pouvoir
 * remonter à une personne (codex §26 et §27).
 */
final class Privacy
{
    /**
     * Condensé irréversible d'une adresse IP.
     */
    public static function hashIp(?string $ip): ?string
    {
        return self::hash($ip);
    }

    /**
     * Condensé irréversible d'un agent utilisateur.
     */
    public static function hashUserAgent(?string $userAgent): ?string
    {
        return self::hash($userAgent);
    }

    /**
     * Condensé SHA-256 salé par la clé d'application.
     *
     * La clé n'étant jamais versionnée, un condensé exporté hors du serveur
     * reste inexploitable.
     */
    public static function hash(?string $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        return hash_hmac('sha256', $value, (string) Config::get('app.key'));
    }

    /**
     * Masque un numéro de téléphone pour l'affichage dans un journal ou un
     * export non nominatif : « 06 12 34 56 78 » devient « 06 •• •• •• 78 ».
     */
    public static function maskPhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if (strlen($digits) < 4) {
            return '••';
        }

        return substr($digits, 0, 2).' •• •• •• '.substr($digits, -2);
    }

    /**
     * Masque une adresse électronique : « jean.dupont@example.fr » devient
     * « j•••@example.fr ».
     */
    public static function maskEmail(?string $email): string
    {
        if (blank($email) || ! str_contains((string) $email, '@')) {
            return '•••';
        }

        [$local, $domain] = explode('@', (string) $email, 2);

        return mb_substr($local, 0, 1).'•••@'.$domain;
    }
}
