<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BootcampCbtQuestion extends Model
{
    protected $table = 'bootcamp_cbt_questions';

    protected $fillable = [
        'bootcamp_cbt_id',
        'passage_text',
        'question_text',
        'image_url',
        'options',
        'correct_option',
        'explanation',
        'order_index',
    ];

    protected $hidden = [
        'correct_option',
        'explanation',
    ];

    protected $casts = [
        'options' => 'array',
        'order_index' => 'integer',
    ];

    public function cbt()
    {
        return $this->belongsTo(BootcampCbt::class, 'bootcamp_cbt_id');
    }
}
