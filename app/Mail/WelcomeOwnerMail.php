<?php

namespace App\Mail;

use App\Models\Business;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeOwnerMail extends Mailable
{
    use Queueable, SerializesModels;

    public User $user;

    public Business $business;

    public function __construct(User $user, Business $business)
    {
        $this->user = $user;
        $this->business = $business;
    }

    public function build()
    {
        return $this->subject('Welcome to DukaPro — ' . $this->business->name)
            ->view('emails.welcome-owner');
    }
}
