<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConsentPurpose;
use Database\Factories\ConsentLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Registre des consentements (codex §27).
 *
 * Chaque entrée conserve le texte exact affiché à la personne au moment où
 * elle a coché la case : c'est cette formulation qui fait foi en cas de
 * contestation, et non la version actuelle du formulaire.
 */
class ConsentLog extends Model
{
    /** @use HasFactory<ConsentLogFactory> */
    use HasFactory;

    protected $fillable = [
        'consentable_type',
        'consentable_id',
        'purpose',
        'statement',
        'version',
        'granted',
        'granted_at',
        'revoked_at',
        'ip_hash',
        'user_agent_hash',
    ];

    protected function casts(): array
    {
        return [
            'purpose' => ConsentPurpose::class,
            'granted' => 'boolean',
            'granted_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function consentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isActive(): bool
    {
        return $this->granted && $this->revoked_at === null;
    }

    /**
     * Retire le consentement sans effacer la preuve qu'il avait été donné.
     */
    public function revoke(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        $this->revoked_at = now();

        return $this->save();
    }
}
