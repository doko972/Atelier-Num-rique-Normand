<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Export CSV en flux, lisible par un tableur français.
 *
 * Deux précautions notables :
 * le fichier commence par une marque d'ordre des octets, sans quoi Excel
 * affiche les accents de travers ; et toute valeur commençant par `=`, `+`,
 * `-` ou `@` est neutralisée pour empêcher l'injection de formule.
 */
final class CsvExporter
{
    private const string BOM = "\u{FEFF}";

    private const string DELIMITER = ';';

    /**
     * @param  array<int, string>  $headers
     * @param  Builder<*>  $query
     * @param  callable(mixed): array<int, mixed>  $mapper
     */
    public static function stream(
        string $filename,
        array $headers,
        Builder $query,
        callable $mapper,
        int $chunkSize = 500,
    ): StreamedResponse {
        return response()->streamDownload(
            function () use ($headers, $query, $mapper, $chunkSize): void {
                $handle = fopen('php://output', 'wb');

                echo self::BOM;

                fputcsv($handle, $headers, self::DELIMITER, escape: '');

                $query->chunkById($chunkSize, function ($records) use ($handle, $mapper): void {
                    foreach ($records as $record) {
                        fputcsv(
                            $handle,
                            array_map(self::sanitise(...), $mapper($record)),
                            self::DELIMITER,
                            escape: '',
                        );
                    }
                });

                fclose($handle);
            },
            $filename,
            [
                'Content-Type' => 'text/csv; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache',
            ],
        );
    }

    /**
     * Neutralise les valeurs interprétables comme une formule par un tableur.
     */
    public static function sanitise(mixed $value): string
    {
        if ($value === null || $value === false) {
            return '';
        }

        if ($value === true) {
            return 'oui';
        }

        $string = (string) $value;

        if ($string !== '' && str_contains("=+-@\t\r", $string[0])) {
            return "'".$string;
        }

        return $string;
    }

    /**
     * Nom de fichier horodaté.
     */
    public static function filename(string $prefix): string
    {
        return sprintf('%s-%s.csv', $prefix, now()->format('Y-m-d-Hi'));
    }
}
