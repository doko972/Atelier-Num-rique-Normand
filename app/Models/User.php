<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\Permission as PermissionEnum;
use App\Enums\UserRole;
use App\Models\Concerns\Auditable;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;

/**
 * Compte de l'espace d'administration.
 *
 * L'espace public ne nécessite aucun compte : les visiteurs ne sont jamais
 * enregistrés ici (codex §23).
 */
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use Auditable, HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'role_id',
        'name',
        'email',
        'phone',
        'password',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password_changed_at' => 'datetime',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted:array',
        ];
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function assignedAppointments(): HasMany
    {
        return $this->hasMany(Appointment::class, 'assigned_to');
    }

    public function appointmentNotes(): HasMany
    {
        return $this->hasMany(AppointmentNote::class);
    }

    // -------------------------------------------------------------------------
    // Rôles et permissions
    // -------------------------------------------------------------------------

    /**
     * Rôle sous forme d'enum, seule source de la hiérarchie.
     */
    public function roleEnum(): ?UserRole
    {
        return $this->role?->toEnum();
    }

    public function isSuperAdmin(): bool
    {
        return $this->roleEnum() === UserRole::SuperAdmin;
    }

    /**
     * Le compte atteint-il au moins le niveau hiérarchique demandé ?
     */
    public function hasRoleAtLeast(UserRole $role): bool
    {
        return $this->roleEnum()?->atLeast($role) ?? false;
    }

    /**
     * Le compte détient-il la permission demandée ?
     *
     * La matrice est lue depuis la base afin de rester ajustable, mais un
     * compte désactivé ne détient jamais aucune permission.
     */
    public function hasPermission(PermissionEnum|string $permission): bool
    {
        if (! $this->is_active) {
            return false;
        }

        $slug = $permission instanceof PermissionEnum ? $permission->value : $permission;

        return $this->permissionSlugs()->contains($slug);
    }

    /**
     * Slugs des permissions du rôle, chargés une seule fois par requête.
     *
     * @return Collection<int, string>
     */
    public function permissionSlugs(): Collection
    {
        $this->loadMissing('role.permissions');

        return $this->role?->permissions->pluck('slug') ?? collect();
    }

    /**
     * Un compte ne peut agir que sur des comptes de niveau strictement
     * inférieur au sien : cela empêche toute escalade de privilèges.
     */
    public function canManage(self $other): bool
    {
        if ($this->is($other)) {
            return false;
        }

        $mine = $this->roleEnum()?->level() ?? 0;
        $theirs = $other->roleEnum()?->level() ?? 0;

        return $mine > $theirs;
    }

    // -------------------------------------------------------------------------
    // Portées
    // -------------------------------------------------------------------------

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Comptes pouvant se voir affecter une demande de rendez-vous.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeAdvisers(Builder $query): Builder
    {
        return $query->active()->whereHas(
            'role',
            fn (Builder $role) => $role->where('level', '>=', UserRole::Adviser->level()),
        );
    }

    public function auditLabel(): string
    {
        return $this->name;
    }
}
