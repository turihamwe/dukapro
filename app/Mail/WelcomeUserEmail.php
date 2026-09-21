<?php

namespace App\Mail;

use App\Models\Business;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class WelcomeUserEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $tries = 3;

    public $backoff = [60, 180, 300];

    public User $user;

    public Business $business;

    public function __construct(User $user, Business $business)
    {
        $this->user = $user;
        $this->business = $business;
    }

    public function build()
    {
        return $this->subject('Welcome to '.platform_brand('name').' - '.$this->business->name)
            ->view('emails.welcome-owner');
    }
}
