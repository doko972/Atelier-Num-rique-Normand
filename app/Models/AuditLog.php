<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Privacy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

/**
 * Journal des actions d'administration (codex §24 et §43).
 *
 * Le journal est en écriture seule : aucune interface ne permet de modifier
 * ou de supprimer une entrée, seule la purge planifiée les efface au terme de
 * la durée de conservation.
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    /**
     * Interrupteur de journalisation.
     *
     * Utilisé pendant l'amorçage de la base : créer les contenus de départ
     * n'est pas une action d'administration et n'a pas à figurer au journal.
     */
    protected static bool $recording = true;

    /**
     * Exécute une opération sans journaliser les modifications de modèles.
     *
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public static function withoutRecording(callable $callback): mixed
    {
        static::$recording = false;

        try {
            return $callback();
        } finally {
            static::$recording = true;
        }
    }

    protected $fillable = [
        'user_id',
        'user_label',
        'action',
        'auditable_type',
        'auditable_id',
        'subject_label',
        'old_values',
        'new_values',
        'channel',
        'ip_hash',
    ];

    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * Enregistre une action.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public static function record(
        string $action,
        ?Model $subject = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        string $channel = 'admin',
    ): ?self {
        if (! static::$recording) {
            return null;
        }

        $user = Auth::user();

        return static::create([
            'user_id' => $user?->getKey(),
            'user_label' => $user?->name,
            'action' => $action,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'subject_label' => method_exists($subject, 'auditLabel')
                ? $subject->auditLabel()
                : null,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'channel' => $channel,
            'ip_hash' => Privacy::hashIp(request()->ip()),
        ]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Nom de l'auteur, même si son compte a été supprimé depuis.
     */
    public function authorName(): string
    {
        return $this->user?->name
            ?? $this->user_label
            ?? __('admin.common.system');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeRecent(Builder $query): Builder
    {
        return $query->orderByDesc('created_at')->orderByDesc('id');
    }
}
