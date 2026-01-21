<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserVerifiedMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $username;
    public string $full_name;

    public function __construct($user)
    {
        $this->username  = $user->username;
        $this->full_name = $user->full_name;
    }

    public function build()
    {
        return $this
            ->subject('Akun Koperasi Anda Telah Aktif')
            ->view('mails.user_verified');
    }
}
