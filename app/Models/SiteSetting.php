<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use App\Services\SettingsService;
use Database\Factories\SiteSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * Paramètre administrable du site.
 *
 * L'accès se fait par le service {@see SettingsService}, qui met
 * l'ensemble en cache : ce modèle n'est manipulé que par le back-office.
 */
class SiteSetting extends Model
{
    /** @use HasFactory<SiteSettingFactory> */
    use Auditable, HasFactory;

    public const string CACHE_KEY = 'site_settings';

    protected $fillable = [
        'key',
        'value',
        'type',
        'group',
        'label',
        'help',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // Toute écriture invalide le cache : le site reflète immédiatement
        // le nouveau numéro de téléphone ou les nouveaux horaires.
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by')->withTrashed();
    }

    /**
     * Valeur convertie selon le type déclaré.
     */
    public function typedValue(): mixed
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOL),
            'integer' => $this->value === null ? null : (int) $this->value,
            'json' => json_decode((string) $this->value, true, flags: JSON_THROW_ON_ERROR),
            default => $this->value,
        };
    }

    public function getRouteKeyName(): string
    {
        return 'key';
    }

    public function auditLabel(): string
    {
        return $this->key;
    }
}
