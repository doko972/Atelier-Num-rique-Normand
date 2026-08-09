<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\GuideStepFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Étape numérotée d'une fiche pratique.
 */
class GuideStep extends Model
{
    /** @use HasFactory<GuideStepFactory> */
    use HasFactory;

    protected $fillable = [
        'practical_guide_id',
        'position',
        'title',
        'body',
        'image_path',
        'image_alt',
        'tip',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function guide(): BelongsTo
    {
        return $this->belongsTo(PracticalGuide::class, 'practical_guide_id');
    }
}
