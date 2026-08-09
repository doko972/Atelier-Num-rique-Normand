<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\AppointmentNoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Note interne attachée à une demande de rendez-vous.
 *
 * Ces notes ne sont jamais communiquées à la personne concernée par un canal
 * automatique, mais elles font partie de ses données personnelles et sont donc
 * incluses dans un export ou une suppression RGPD.
 */
class AppointmentNote extends Model
{
    /** @use HasFactory<AppointmentNoteFactory> */
    use HasFactory;

    protected $fillable = [
        'appointment_id',
        'user_id',
        'body',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function authorName(): string
    {
        if ($this->is_system) {
            return __('admin.appointments.system_note_author');
        }

        return $this->author?->name ?? __('admin.common.deleted_account');
    }
}
