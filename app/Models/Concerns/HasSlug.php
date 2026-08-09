<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Génère et garantit l'unicité d'un identifiant d'URL lisible.
 *
 * Les URL lisibles sont exigées par le codex §28 ; elles aident aussi les
 * personnes qui recopient une adresse à la main depuis un document papier.
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function (Model $model): void {
            /** @var static $model */
            $sourceField = $model->slugSourceField();

            if (blank($model->slug) && filled($model->{$sourceField})) {
                $model->slug = $model->generateUniqueSlug((string) $model->{$sourceField});
            }
        });
    }

    /**
     * Champ à partir duquel le slug est construit.
     */
    public function slugSourceField(): string
    {
        return 'title';
    }

    /**
     * Construit un slug unique en suffixant un compteur si nécessaire.
     */
    public function generateUniqueSlug(string $source): string
    {
        $base = Str::slug($source) ?: 'element';
        $slug = $base;
        $suffix = 1;

        while ($this->slugExists($slug)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    protected function slugExists(string $slug): bool
    {
        $query = static::query()
            ->where('slug', $slug)
            ->when($this->exists, fn ($query) => $query->whereKeyNot($this->getKey()));

        if (method_exists($this, 'bootSoftDeletes')) {
            $query->withTrashed();
        }

        return $query->exists();
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
