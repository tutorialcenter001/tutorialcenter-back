<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CoursesEnrollment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'course_id',
        'student_id',
        'start_date',
        'end_date',
        'billing_cycle',
        'cost',
        'status',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'cost' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function course()
    {
        return $this->belongsTo(Course::class)->withTrashed();
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subjects()
    {
        return $this->hasMany(SubjectsEnrollment::class, 'course_enrollment_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'course_enrollment_id');
    }

    public function subjectsEnrollments()
    {
        return $this->hasMany(SubjectsEnrollment::class, 'course_enrollment_id');
    }

    public function isActive(): bool
    {
        return
            $this->status === 'active'
            && (
                is_null($this->end_date)
                || $this->end_date->gte(now())
            )
            && $this->payments()
            ->where('status', 'successful')
            ->exists();
    }

    public function scopeActiveSubscription($query)
    {
        return $query
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }
}
