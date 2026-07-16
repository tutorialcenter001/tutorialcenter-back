<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{

    protected $table = 'feedbacks';

    protected $fillable = [
        'feedbacker_type',
        'feedbacker_id',
        'feedbackable_type',
        'feedbackable_id',
        'rating',
        'title',
        'comment',
        'ratings',
        'would_recommend',
        'is_anonymous',
        'status',
    ];

    protected $casts = [
        'id' => 'integer',
        'feedbacker_id' => 'integer',
        'feedbackable_id' => 'integer',
        'rating' => 'integer',
        'ratings' => 'array',
        'would_recommend' => 'boolean',
        'is_anonymous' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * User who submitted the feedback.
     * (Student, Guardian, Staff, etc.)
     */
    public function feedbacker()
    {
        return $this->morphTo();
    }

    /**
     * Model being reviewed.
     * (Course, Lesson, ExamAttempt, Live Class, etc.)
     */
    public function feedbackable()
    {
        return $this->morphTo();
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeHidden($query)
    {
        return $query->where('status', 'hidden');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    /**
     * Returns rating as a percentage (0 - 100)
     */
    public function getRatingPercentageAttribute(): float
    {
        return ($this->rating / 5) * 100;
    }

    /**
     * Returns star string
     */
    public function getStarsAttribute(): string
    {
        return str_repeat('★', $this->rating)
            . str_repeat('☆', 5 - $this->rating);
    }
}