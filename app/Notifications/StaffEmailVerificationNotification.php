<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class StaffEmailVerificationNotification extends Notification
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
        $verifyUrl = config('app.frontend_url') . '/staff-verify-email?email=' . $notifiable->email . '&token=' . $this->token;
        // $phoneVerifyUrl = config('app.frontend_url') . '/verify-phone?telephone=' . urlencode($notifiable->telephone);

        return (new MailMessage)
            ->subject('Verify Your Staff Account')
            ->greeting(
                'Dear ' .
                $notifiable->firstname . ' ' .
                $notifiable->middlename . ' ' .
                $notifiable->surname . ','
            )
            ->line('Welcome to the platform.')
            ->line(
                'Your staff account has been successfully created with the role of ' .
                ucfirst($notifiable->role) . '.'
            )
            ->line('To complete your registration and activate your account, please verify your email address by clicking the button below.')
            ->action('Verify Email', $verifyUrl)
            ->line("Your temporary password is: " . $notifiable->staff_id)
            ->line('This verification link will expire in 30 minutes.')
            ->line('Or use the OTP below:')
            ->line($this->token)
            ->line('For any assistance, please contact support team or management.');
    }
}
