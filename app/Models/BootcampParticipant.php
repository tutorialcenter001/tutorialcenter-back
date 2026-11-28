<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BootcampParticipant extends Model
{
    protected $table = 'bootcamp_participants';

    protected $fillable = [
        'phone_normalized',
        'phone_raw',
        'full_name',
        'pin_hash',
        'last_seen_at',
    ];

    protected $hidden = [
        'pin_hash',
    ];

    protected $casts = [
        'last_seen_at' => 'datetime',
    ];

    /**
     * Normalize phone numbers canonically so 0801..., +234801..., and 234801...
     * map to the same participant record.
     */
    public static function normalizePhone(string $raw): string
    {
        $digits = preg_replace('/\D+/', '', trim($raw)) ?? '';

        if ($digits === '') {
            return '';
        }

        // Convert Nigerian local 11-digit format (0XXXXXXXXXX) to 234XXXXXXXXXX
        if (strlen($digits) === 11 && str_starts_with($digits, '0')) {
            return '234' . substr($digits, 1);
        }

        // Convert 10-digit Nigerian subscriber number missing leading 0 (e.g. 8012345678)
        if (strlen($digits) === 10 && preg_match('/^[789][01]\d{8}$/', $digits)) {
            return '234' . $digits;
        }

        return $digits;
    }

    public function attempts()
    {
        return $this->hasMany(BootcampCbtAttempt::class, 'participant_id');
    }
}
