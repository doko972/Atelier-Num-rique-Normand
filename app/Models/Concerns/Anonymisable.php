<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Anonymisation d'un enregistrement contenant des données personnelles.
 *
 * L'anonymisation est préférée à la suppression : elle honore le droit à
 * l'effacement tout en préservant les statistiques agrégées attendues par les
 * partenaires financeurs (codex §27 et §36).
 *
 * @mixin Model
 */
trait Anonymisable
{
    /**
     * Champs remis à une valeur neutre lors de l'anonymisation.
     *
     * @return array<string, mixed>
     */
    abstract public function anonymisedAttributes(): array;

    public function isAnonymised(): bool
    {
        return $this->anonymised_at !== null;
    }

    /**
     * Remplace les données personnelles par des valeurs neutres.
     *
     * L'opération est idempotente : ré-anonymiser un enregistrement déjà
     * traité ne provoque ni erreur ni seconde écriture.
     */
    public function anonymise(): bool
    {
        if ($this->isAnonymised()) {
            return false;
        }

        $this->forceFill($this->anonymisedAttributes());
        $this->anonymised_at = now();

        return $this->save();
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeNotAnonymised($query)
    {
        return $query->whereNull('anonymised_at');
    }
}
