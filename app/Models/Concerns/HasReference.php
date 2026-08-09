<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Attribue une référence courte, lisible et communicable par téléphone.
 *
 * Format : PRÉFIXE-ANNÉE-XXXX (exemple : RDV-2026-4K7P). Les caractères
 * ambigus à l'oral ou à l'écrit (0/O, 1/I, 8/B) sont exclus.
 *
 * @mixin Model
 */
trait HasReference
{
    protected const string REFERENCE_ALPHABET = '23456789ACDEFGHJKLMNPQRTUVWXYZ';

    public static function bootHasReference(): void
    {
        static::creating(function (Model $model): void {
            /** @var static $model */
            if (blank($model->reference)) {
                $model->reference = $model->generateReference();
            }
        });
    }

    /**
     * Préfixe identifiant le type de demande.
     */
    abstract public function referencePrefix(): string;

    public function generateReference(): string
    {
        $year = now()->format('Y');
        $alphabetLength = \strlen(self::REFERENCE_ALPHABET);

        do {
            $suffix = '';

            for ($i = 0; $i < 4; $i++) {
                $suffix .= self::REFERENCE_ALPHABET[random_int(0, $alphabetLength - 1)];
            }

            $reference = Str::upper("{$this->referencePrefix()}-{$year}-{$suffix}");
        } while ($this->referenceExists($reference));

        return $reference;
    }

    /**
     * Une référence déjà attribuée, même à un enregistrement supprimé en
     * douceur, ne doit jamais être réutilisée.
     */
    protected function referenceExists(string $reference): bool
    {
        $query = static::query()->where('reference', $reference);

        if (method_exists($this, 'bootSoftDeletes')) {
            $query->withTrashed();
        }

        return $query->exists();
    }
}
