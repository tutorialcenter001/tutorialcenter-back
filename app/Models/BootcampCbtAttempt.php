<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BootcampCbtAttempt extends Model
{
    protected $table = 'bootcamp_cbt_attempts';

    protected $fillable = [
        'bootcamp_cbt_id',
        'participant_id',
        'answers',
        'score',
        'total_questions',
        'time_taken_seconds',
        'started_at',
        'submitted_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'score' => 'integer',
        'total_questions' => 'integer',
        'time_taken_seconds' => 'integer',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
    ];

    public function cbt()
    {
        return $this->belongsTo(BootcampCbt::class, 'bootcamp_cbt_id');
    }

    public function participant()
    {
        return $this->belongsTo(BootcampParticipant::class, 'participant_id');
    }
}
