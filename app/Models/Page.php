<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContentStatus;
use App\Models\Concerns\Auditable;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\TracksAuthor;
use Database\Factories\PageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Page éditoriale libre ou page légale.
 *
 * Les pages système (mentions légales, confidentialité, cookies, accessibilité)
 * portent une clé stable : le pied de page pointe vers elles quel que soit
 * leur titre, et elles ne peuvent pas être supprimées.
 */
class Page extends Model
{
    /** @use HasFactory<PageFactory> */
    use Auditable, HasFactory, HasSlug, SoftDeletes, TracksAuthor;

    public const string KEY_LEGAL = 'mentions-legales';

    public const string KEY_PRIVACY = 'politique-de-confidentialite';

    public const string KEY_COOKIES = 'gestion-des-cookies';

    public const string KEY_ACCESSIBILITY = 'declaration-accessibilite';

    public const string KEY_ABOUT = 'a-propos';

    public const string KEY_ONLINE_PROCEDURES = 'demarches-en-ligne';

    public const string KEY_SECURITY = 'securite-et-arnaques';

    protected $fillable = [
        'title',
        'slug',
        'key',
        'summary',
        'body',
        'status',
        'is_system',
        'show_in_footer',
        'position',
        'meta_title',
        'meta_description',
        'noindex',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => ContentStatus::class,
            'is_system' => 'boolean',
            'show_in_footer' => 'boolean',
            'noindex' => 'boolean',
            'position' => 'integer',
            'published_at' => 'datetime',
        ];
    }

    public function files(): MorphMany
    {
        return $this->morphMany(DownloadableFile::class, 'attachable')->orderBy('position');
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
    public function scopeInFooter(Builder $query): Builder
    {
        return $query->published()
            ->where('show_in_footer', true)
            ->orderBy('position')
            ->orderBy('title');
    }

    /**
     * Récupère une page système par sa clé.
     */
    public static function findByKey(string $key): ?self
    {
        return static::query()->where('key', $key)->first();
    }
}
