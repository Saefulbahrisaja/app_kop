<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ForgotPasswordOtpMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $otp;
    public string $name;

    public function __construct(string $otp, string $name)
    {
        $this->otp  = $otp;
        $this->name = $name;
    }

    public function build()
    {
        return $this
            ->subject('Kode OTP Reset Password')
            ->view('mails.forgot-password-otp');
    }
}
