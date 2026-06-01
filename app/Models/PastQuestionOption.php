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
        'is_correct' => 'boolean',
        'id' => 'integer',
        'past_question_id' => 'integer',
    ];

    // protected $hidden = ['is_correct'];

    public function question()
    {
        return $this->belongsTo(PastQuestion::class, 'past_question_id');
    }
}
