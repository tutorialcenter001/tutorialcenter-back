<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExamAttemptAnswer extends Model
{
    protected $fillable = [
        'exam_attempt_id',
        'past_question_id',
        'past_question_option_id',
        // 'is_correct',
    ];

    public function attempt()
    {
        return $this->belongsTo(ExamAttempt::class);
    }

    public function question()
    {
        return $this->belongsTo(PastQuestion::class);
    }

    public function option()
    {
        return $this->belongsTo(PastQuestionOption::class, 'past_question_option_id');
    }

    public function getIsCorrectAttribute()
    {
        return $this->option?->is_correct ?? false;
    }
}
