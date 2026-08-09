<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\Auditable;
use Database\Factories\AccessibilityReportFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Audit d'accessibilité, publié dans la déclaration d'accessibilité.
 *
 * La déclaration doit mentionner un état de conformité honnête : la loi
 * n'exige pas la perfection, mais elle exige la transparence.
 */
class AccessibilityReport extends Model
{
    /** @use HasFactory<AccessibilityReportFactory> */
    use Auditable, HasFactory;

    public const string LEVEL_NONE = 'none';

    public const string LEVEL_PARTIAL = 'partial';

    public const string LEVEL_FULL = 'full';

    protected $fillable = [
        'title',
        'audited_on',
        'referential',
        'compliance_rate',
        'level',
        'summary',
        'non_conformities',
        'improvement_plan',
        'auditor',
        'report_path',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'audited_on' => 'date',
            'compliance_rate' => 'decimal:2',
            'non_conformities' => 'array',
            'is_published' => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderByDesc('audited_on');
    }

    public function levelLabel(): string
    {
        return __("site.accessibility.level.{$this->level}");
    }
}
