<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Permission as PermissionEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Permission élémentaire attribuable à un rôle.
 */
class Permission extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'group',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class);
    }

    public function toEnum(): ?PermissionEnum
    {
        return PermissionEnum::tryFrom($this->slug);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
