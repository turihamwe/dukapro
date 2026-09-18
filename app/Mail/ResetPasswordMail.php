<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public string $resetUrl;

    public function __construct(User $user, string $resetUrl)
    {
        $this->user = $user;
        $this->resetUrl = $resetUrl;
    }

    public function build()
    {
        return $this->subject('Reset your password — ' . platform_brand('name'))
            ->view('emails.reset-password');
    }
}
