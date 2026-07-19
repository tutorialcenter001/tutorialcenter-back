<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Guardian extends Model
{
    use SoftDeletes, Notifiable, HasApiTokens;

    protected $fillable = [
        'firstname',
        'surname',
        'email',
        'tel',
        'password',
        'gender',
        'profile_picture',
        'date_of_birth',
        'email_verified_at',
        'tel_verified_at',
        'location',
        'address',
    ];

    protected $hidden = ['password'];

    protected $casts = [
        'date_of_birth' => 'date',
        'email_verified_at' => 'datetime',
        'tel_verified_at' => 'datetime',
    ];

    public function emailVerification()
    {
        return $this->morphOne(EmailVerification::class, 'verifiable');
    }

    public function students()
    {
        return $this->belongsToMany(
            Student::class,
            'guardian_students',
            'guardian_id',
            'student_id'
        )
            ->using(GuardianStudent::class)
            ->withPivot('relationship')
            ->withTimestamps();
    }

    // Each guardian can have many feedbacks
    public function feedbacks()
    {
        return $this->morphMany(
            Feedback::class,
            'feedbacker'
        );
    }

    public function supportTickets()
    {
        return $this->morphMany(
            SupportTicket::class,
            'requester'
        );
    }

    public function supportMessages()
    {
        return $this->morphMany(
            SupportTicketMessage::class,
            'sender'
        );
    }
}
