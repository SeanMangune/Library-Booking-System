<?php

namespace App\Mail;

use App\Models\Booking;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BookingRescheduledMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param array<string, mixed> $previousSchedule
     */
    public function __construct(
        public Booking $booking,
        public array $previousSchedule = [],
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your SmartSpace Booking Was Rescheduled',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.bookings.rescheduled',
            with: [
                'booking' => $this->booking,
                'previousSchedule' => $this->previousSchedule,
            ],
        );
    }
}
