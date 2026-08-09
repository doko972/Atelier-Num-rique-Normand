<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\TracksAuthor;
use Database\Factories\OfficialLinkFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Lien vers un organisme officiel, administrable depuis le back-office
 * (codex §13). Ces adresses changent : elles ne sont pas codées en dur.
 */
class OfficialLink extends Model
{
    /** @use HasFactory<OfficialLinkFactory> */
    use Auditable, HasFactory, SoftDeletes, TracksAuthor;

    public const string CATEGORY_SECURITY = 'security';

    public const string CATEGORY_PROCEDURES = 'procedures';

    public const string CATEGORY_SUPPORT = 'support';

    protected $fillable = [
        'label',
        'url',
        'description',
        'category',
        'status',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'position' => 'integer',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', ContentStatus::Published);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position')->orderBy('label');
    }

    /**
     * Nom de domaine, affiché pour que la personne puisse vérifier vers quel
     * site elle est envoyée avant de cliquer.
     */
    public function host(): string
    {
        return (string) parse_url($this->url, PHP_URL_HOST);
    }
}
