<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\TracksAuthor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Groupe de mots équivalents pour la recherche du site public.
 *
 * Taper l'un des mots du groupe revient à les chercher tous : « mail »
 * trouve les fiches qui parlent de « courriel ».
 */
class SearchSynonym extends Model
{
    use Auditable, SoftDeletes, TracksAuthor;

    public const string CACHE_KEY = 'search.synonyms';

    protected $fillable = [
        'label',
        'words',
    ];

    protected function casts(): array
    {
        return [
            'words' => 'array',
        ];
    }

    protected static function booted(): void
    {
        $forget = fn () => cache()->forget(self::CACHE_KEY);

        static::saved($forget);
        static::deleted($forget);
        static::restored($forget);
    }

    /**
     * Tous les mots du groupe, libellé compris.
     *
     * @return array<int, string>
     */
    public function allWords(): array
    {
        return array_values(array_unique([$this->label, ...($this->words ?? [])]));
    }
}
