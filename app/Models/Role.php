<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Rôle d'administration.
 *
 * Les rôles système correspondent aux valeurs de l'enum {@see UserRole} ; la
 * table permet en plus d'ajuster la matrice des permissions sans redéployer.
 */
class Role extends Model
{
    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    protected $fillable = [
        'slug',
        'name',
        'description',
        'level',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
            'is_system' => 'boolean',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class);
    }

    /**
     * Enum correspondant, lorsque le rôle est un rôle système.
     */
    public function toEnum(): ?UserRole
    {
        return UserRole::tryFrom($this->slug);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
