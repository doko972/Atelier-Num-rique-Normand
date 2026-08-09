<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Journalise les créations, modifications et suppressions d'un modèle.
 *
 * Aucun mot de passe, jeton ni donnée sensible n'est jamais écrit dans le
 * journal : les champs listés par auditExcluded() sont retirés (codex §43).
 *
 * @mixin Model
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model): void {
            /** @var static $model */
            AuditLog::record('created', $model, null, $model->auditableAttributes($model->getAttributes()));
        });

        static::updated(function (Model $model): void {
            /** @var static $model */
            $changes = $model->auditableAttributes($model->getChanges());

            if ($changes === []) {
                return;
            }

            $original = array_intersect_key($model->getOriginal(), $changes);

            AuditLog::record('updated', $model, $model->auditableAttributes($original), $changes);
        });

        static::deleted(function (Model $model): void {
            /** @var static $model */
            $action = method_exists($model, 'isForceDeleting') && $model->isForceDeleting()
                ? 'force_deleted'
                : 'deleted';

            AuditLog::record($action, $model);
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function (Model $model): void {
                AuditLog::record('restored', $model);
            });
        }
    }

    /**
     * Champs exclus du journal d'audit.
     *
     * @return array<int, string>
     */
    public function auditExcluded(): array
    {
        return array_merge([
            'password',
            'remember_token',
            'two_factor_secret',
            'two_factor_recovery_codes',
            'ip_hash',
            'user_agent_hash',
            'updated_at',
            'created_at',
        ], $this->auditHiddenFields());
    }

    /**
     * Champs supplémentaires à ne pas journaliser.
     *
     * Les modèles porteurs de données personnelles redéfinissent cette
     * méthode : le journal ne doit contenir que la référence et le statut,
     * jamais l'identité de la personne.
     *
     * @return array<int, string>
     */
    protected function auditHiddenFields(): array
    {
        return [];
    }

    /**
     * Libellé lisible du modèle dans le journal.
     */
    public function auditLabel(): string
    {
        foreach (['title', 'name', 'label', 'reference', 'question', 'key'] as $field) {
            if (filled($this->{$field} ?? null)) {
                return (string) $this->{$field};
            }
        }

        return class_basename($this).' #'.$this->getKey();
    }

    /**
     * Retire les champs sensibles avant journalisation.
     *
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function auditableAttributes(array $attributes): array
    {
        return array_diff_key($attributes, array_flip($this->auditExcluded()));
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable')->latest('created_at');
    }
}
