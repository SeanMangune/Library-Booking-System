<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistrationOtpMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public string $email, public string $name, public string $otpCode)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your SmartSpace Registration Verification Code',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.auth.registration-otp',
        );
    }
}
