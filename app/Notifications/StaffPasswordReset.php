<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class StaffPasswordReset extends Notification
{
    use Queueable;

    public function __construct(public string $token)
    {
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Reset Your Password')
            ->greeting('Hello!')
            ->line('You have requested to reset your password.')
            ->line('Use the OTP below to reset your password:')
            ->line($this->token)
            ->line('This OTP will expire in 30 minutes.')
            ->line('If you did not request this password reset, please ignore this email.');
    }
}