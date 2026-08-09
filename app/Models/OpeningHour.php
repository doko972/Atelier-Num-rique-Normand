<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\FrenchFormat;
use Carbon\CarbonImmutable;
use Database\Factories\OpeningHourFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Créneau d'appel téléphonique (codex §35).
 *
 * Hors de ces horaires, le site affiche un message invitant à laisser un
 * message vocal plutôt que de laisser croire à une absence de réponse.
 */
class OpeningHour extends Model
{
    /** @use HasFactory<OpeningHourFactory> */
    use HasFactory;

    public const string CACHE_KEY = 'opening_hours';

    protected $fillable = [
        'weekday',
        'opens_at',
        'closes_at',
        'is_closed',
        'note',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'weekday' => 'integer',
            'is_closed' => 'boolean',
            'position' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('weekday')->orderBy('position');
    }

    /**
     * Nom du jour en français (« lundi », « mardi »…).
     */
    public function weekdayName(): string
    {
        return CarbonImmutable::now()
            ->startOfWeek(CarbonImmutable::MONDAY)
            ->addDays($this->weekday - 1)
            ->locale('fr')
            ->dayName;
    }

    /**
     * Plage formatée : « de 09h00 à 12h00 ».
     */
    public function range(): string
    {
        if ($this->is_closed || blank($this->opens_at) || blank($this->closes_at)) {
            return __('site.contact.closed');
        }

        return FrenchFormat::range($this->opens_at, $this->closes_at);
    }

    /**
     * Ce créneau couvre-t-il l'instant donné ?
     */
    public function covers(CarbonImmutable $moment): bool
    {
        if ($this->is_closed || blank($this->opens_at) || blank($this->closes_at)) {
            return false;
        }

        if ($moment->dayOfWeekIso !== $this->weekday) {
            return false;
        }

        $time = $moment->format('H:i:s');

        return $time >= $this->normaliseTime($this->opens_at)
            && $time <= $this->normaliseTime($this->closes_at);
    }

    /**
     * Normalise une heure quelle que soit sa représentation renvoyée par le
     * pilote de base de données, pour permettre une comparaison de chaînes.
     */
    protected function normaliseTime(mixed $time): string
    {
        return $time instanceof \DateTimeInterface ? $time->format('H:i:s') : (string) $time;
    }
}
