<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class StudentEmailVerification extends Notification
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
        $verifyUrl = config('app.frontend_url') . "/register/student/email/verify?email=$notifiable->email&token=$this->token";
            return (new MailMessage)
        ->subject('Verify Your Email Address')
        ->greeting('Hello!')
        ->line('Please verify your email address to activate your account.')
        ->action('Verify Email', $verifyUrl)
        ->line('This link will expire in 30 minutes.')
        ->line('Or use the OTP below:')
        ->line($this->token);
        // ->line(new \Illuminate\Support\HtmlString("
        //     <div style='text-align:center; margin:20px 0;'>
        //         <span style='
        //             display:inline-block;
        //             font-size:32px;
        //             font-weight:bold;
        //             letter-spacing:5px;
        //             padding:12px 20px;
        //             border:2px dashed #ccc;
        //             border-radius:8px;
        //             background:#f9f9f9;
        //         '>
        //             {$this->token}
        //         </span>
        //     </div>
        //     <p style='text-align:center; font-size:12px; color:#888;'>
        //         Tap and hold to copy the code
        //     </p>
        // ")
        // );

        // return (new MailMessage)
        //     ->subject('Verify Your Email Address')
        //     ->greeting('Hello!')
        //     ->line('Please verify your email address to activate your account.')
        //     ->action('Verify Email', $verifyUrl)
        //     ->line('This link will expire in 30 minutes.');
        // ->line('OTP: ' . $this->token);
    }

}
