<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\DownloadableFileFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;

/**
 * Document téléchargeable rattaché à une fiche, un atelier ou une page.
 *
 * Le type MIME et la taille sont vérifiés au téléversement (codex §26) et
 * conservés ici afin d'être annoncés dans le lien de téléchargement, comme
 * l'exige le RGAA.
 */
class DownloadableFile extends Model
{
    /** @use HasFactory<DownloadableFileFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'attachable_type',
        'attachable_id',
        'title',
        'description',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
        'checksum',
        'is_public',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'size_bytes' => 'integer',
            'download_count' => 'integer',
            'position' => 'integer',
        ];
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * Extension affichée dans le libellé du lien (« PDF », « DOCX »…).
     */
    public function extension(): string
    {
        return strtoupper(pathinfo($this->original_name, PATHINFO_EXTENSION) ?: 'fichier');
    }

    /**
     * Taille lisible, annoncée à côté du lien de téléchargement.
     */
    public function humanSize(): string
    {
        return Number::fileSize($this->size_bytes, precision: 1, locale: 'fr');
    }

    public function url(): string
    {
        return route('files.download', $this);
    }

    public function exists(): bool
    {
        return Storage::disk($this->disk)->exists($this->path);
    }
}
