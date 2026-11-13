<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

/**
 * Recherche du site public restée sans résultat.
 *
 * Elle dit quels mots les visiteurs emploient réellement, et quels sujets
 * manquent. Les entrées anciennes sont purgées : passé un an, elles ne
 * renseignent plus sur le site tel qu'il est.
 */
class MissedSearch extends Model
{
    use MassPrunable;

    public const int RETENTION_MONTHS = 12;

    protected $fillable = [
        'query',
        'hits',
        'last_searched_at',
    ];

    protected function casts(): array
    {
        return [
            'hits' => 'integer',
            'last_searched_at' => 'datetime',
        ];
    }

    /**
     * Enregistre une recherche infructueuse, ou incrémente son compteur.
     */
    public static function record(string $query): void
    {
        $existing = static::query()->where('query', $query)->first();

        if ($existing !== null) {
            $existing->forceFill([
                'hits' => $existing->hits + 1,
                'last_searched_at' => now(),
            ])->save();

            return;
        }

        static::query()->create([
            'query' => $query,
            'hits' => 1,
            'last_searched_at' => now(),
        ]);
    }

    /**
     * @return Builder<static>
     */
    public function prunable(): Builder
    {
        return static::query()->where('last_searched_at', '<', now()->subMonths(self::RETENTION_MONTHS));
    }
}
