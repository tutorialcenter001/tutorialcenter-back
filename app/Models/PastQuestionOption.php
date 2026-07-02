<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PastQuestionOption extends Model
{
    protected $fillable = [
        'past_question_id',
        'label',
        'option_text',
        'is_correct',
        'sort_order',
    ];

    protected $casts = [
        'id' => 'integer',
        'is_correct' => 'boolean',
        'past_question_id' => 'integer',
    ];

    public function question()
    {
        return $this->belongsTo(PastQuestion::class, 'past_question_id');
    }
}
