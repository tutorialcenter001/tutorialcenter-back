<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class BootcampCbt extends Model
{
    public const TRACK_LABELS = [
        'jamb_english'   => 'JAMB Use of English',
        'olevel_english' => "O'Level English",
        'jamb_maths'     => 'JAMB Mathematics',
        'olevel_maths'   => "O'Level Mathematics",
    ];

    protected $table = 'bootcamp_cbts';

    protected $fillable = [
        'title',
        'subject_track',
        'week_start_date',
        'access_token',
        'duration_minutes',
        'starts_at',
        'expires_at',
        'is_active',
        'created_by_staff_id',
    ];

    protected $casts = [
        'week_start_date' => 'date:Y-m-d',
        'duration_minutes' => 'integer',
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $appends = ['subject_label'];

    public static function generateUniqueToken(): string
    {
        do {
            $token = Str::random(24);
        } while (self::where('access_token', $token)->exists());

        return $token;
    }

    public function getSubjectLabelAttribute(): string
    {
        return self::TRACK_LABELS[$this->subject_track] ?? ucfirst(str_replace('_', ' ', (string) $this->subject_track));
    }

    public function isCurrentlyOpen(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->expires_at && $now->gt($this->expires_at)) {
            return false;
        }

        return true;
    }

    public function questions()
    {
        return $this->hasMany(BootcampCbtQuestion::class, 'bootcamp_cbt_id')->orderBy('order_index')->orderBy('id');
    }

    public function attempts()
    {
        return $this->hasMany(BootcampCbtAttempt::class, 'bootcamp_cbt_id');
    }
}
