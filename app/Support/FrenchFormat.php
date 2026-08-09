<?php

declare(strict_types=1);

namespace App\Support;

use Carbon\CarbonImmutable;
use DateTimeInterface;

/**
 * Mise en forme des dates et des heures en français.
 *
 * Ces méthodes existent pour éviter un piège d'échappement : dans un format
 * de date PHP, `'H\\ h i'` place la barre oblique devant l'espace et non
 * devant le `h`. Le `h` est alors compris comme l'heure sur douze heures, et
 * 17 h 00 s'affiche « 17 05 00 ». Le format correct est `'H\\hi'`.
 *
 * Centraliser la règle évite que l'erreur ne se reproduise à chaque nouvel
 * affichage d'horaire.
 */
final class FrenchFormat
{
    /**
     * Heure seule : « 09h00 ».
     *
     * Accepte aussi bien un objet date qu'une chaîne `H:i:s` renvoyée par le
     * pilote de base de données.
     */
    public static function time(DateTimeInterface|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return self::toCarbon($value)->format('H\\hi');
    }

    /**
     * Plage horaire : « de 09h00 à 17h00 ».
     */
    public static function range(
        DateTimeInterface|string|null $start,
        DateTimeInterface|string|null $end,
    ): string {
        return __('site.contact.hours_range', [
            'start' => self::time($start),
            'end' => self::time($end),
        ]);
    }

    /**
     * Date complète : « lundi 6 août 2026 ».
     */
    public static function date(DateTimeInterface|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return self::toCarbon($value)->locale('fr')->isoFormat('dddd D MMMM YYYY');
    }

    /**
     * Date courte : « 6 août 2026 ».
     */
    public static function shortDate(DateTimeInterface|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        return self::toCarbon($value)->locale('fr')->isoFormat('D MMMM YYYY');
    }

    /**
     * Date et heure : « lundi 6 août 2026 à 09h00 ».
     */
    public static function dateTime(DateTimeInterface|string|null $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $moment = self::toCarbon($value);

        return __('site.common.date_at_time', [
            'date' => $moment->locale('fr')->isoFormat('dddd D MMMM YYYY'),
            'time' => $moment->format('H\\hi'),
        ]);
    }

    private static function toCarbon(DateTimeInterface|string $value): CarbonImmutable
    {
        return $value instanceof DateTimeInterface
            ? CarbonImmutable::instance($value)
            : CarbonImmutable::parse($value);
    }
}
