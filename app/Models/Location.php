<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\TracksAuthor;
use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Lieu d'accueil des ateliers et des permanences.
 */
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use Auditable, HasFactory, HasSlug, SoftDeletes, TracksAuthor;

    protected $fillable = [
        'municipality_id',
        'name',
        'slug',
        'address_line',
        'postal_code',
        'city',
        'phone',
        'latitude',
        'longitude',
        'is_accessible',
        'accessibility_details',
        'access_notes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'is_accessible' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function slugSourceField(): string
    {
        return 'name';
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(Municipality::class);
    }

    public function workshops(): HasMany
    {
        return $this->hasMany(Workshop::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Adresse sur une seule ligne, prête à être affichée ou imprimée.
     */
    public function fullAddress(): string
    {
        return collect([
            $this->address_line,
            trim("{$this->postal_code} {$this->city}"),
        ])->filter()->implode(', ');
    }

    /**
     * Lien d'ouverture dans une application de cartographie (codex §31).
     */
    public function mapUrl(): string
    {
        return 'https://www.openstreetmap.org/search?query='.rawurlencode($this->fullAddress());
    }
}
