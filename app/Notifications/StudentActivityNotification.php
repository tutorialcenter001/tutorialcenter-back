<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class StudentActivityNotification extends Notification
{
    protected $student;
    protected $type;
    protected $data;

    public function __construct($student, string $type, array $data = [])
    {
        $this->student = $student;
        $this->type = $type;
        $this->data = $data;
    }

    public function via($notifiable)
    {
        return ['database']; // add 'mail' later if needed
    }

    public function toArray($notifiable)
    {
        return [
            'type' => $this->type,

            'student' => [
                'id' => $this->student->id,
                'name' => $this->student->firstname . ' ' . $this->student->surname,
            ],

            'message' => $this->buildMessage(),

            'data' => $this->data,

            'meta' => [
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ],

            'time' => now(),
        ];
    }

    // Build a user-friendly message based on the activity type
    protected function buildMessage()
    {
        $contact = $this->student->email ?: $this->student->tel;
        $identity = trim($this->student->firstname . ' ' . $this->student->surname);
        $contactSuffix = $contact ? " ({$contact})" : '';

        return match ($this->type) {
            'login' => "{$identity}{$contactSuffix} just logged in",
            'logout' => "{$identity}{$contactSuffix} just logged out",
            'forget password' => "{$identity}{$contactSuffix} requested a password reset",
            'change password' => "{$identity}{$contactSuffix} changed their password",
            'update profile' => "{$identity}{$contactSuffix} updated their profile",
            'contact change request' => "{$identity}{$contactSuffix} requested a contact change",
            'confirm contact change' => "{$identity}{$contactSuffix} confirmed a contact change",





            'attendance' => "{$identity}{$contactSuffix} attended a class",
            'assignment_submitted' => "{$identity}{$contactSuffix} submitted an assignment",
            'payment_successful' => "{$identity}{$contactSuffix} made a payment",
            'schedule_update' => "Class schedule updated for {$identity}{$contactSuffix}",
            default => "New activity from {$identity}{$contactSuffix}",
        };
    }

    // // Optional: If you want to send email notifications as well
    // public function toMail($notifiable)
    // {
    //     return (new MailMessage)
    //         ->subject('Student Activity Alert')
    //         ->line($this->buildMessage())
    //         ->line('Time: ' . now());
    // }
}

