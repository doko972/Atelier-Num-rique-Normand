<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\TracksAuthor;
use Database\Factories\MunicipalityFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Commune du territoire d'intervention.
 */
class Municipality extends Model
{
    /** @use HasFactory<MunicipalityFactory> */
    use Auditable, HasFactory, HasSlug, SoftDeletes, TracksAuthor;

    protected $fillable = [
        'name',
        'slug',
        'postal_code',
        'insee_code',
        'department',
        'latitude',
        'longitude',
        'distance_km',
        'is_covered',
        'home_visits_available',
        'notes',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'distance_km' => 'integer',
            'is_covered' => 'boolean',
            'home_visits_available' => 'boolean',
            'position' => 'integer',
        ];
    }

    public function slugSourceField(): string
    {
        return 'name';
    }

    public function locations(): HasMany
    {
        return $this->hasMany(Location::class);
    }

    public function workshops(): HasMany
    {
        return $this->hasMany(Workshop::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }

    public function partners(): HasMany
    {
        return $this->hasMany(Partner::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeCovered(Builder $query): Builder
    {
        return $query->where('is_covered', true);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('name');
    }

    public function fullName(): string
    {
        return "{$this->name} ({$this->postal_code})";
    }
}
