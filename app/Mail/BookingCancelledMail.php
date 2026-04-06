<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

use App\Models\Booking;

class BookingCancelledMail extends Mailable
{
    use Queueable, SerializesModels;

    public Booking $booking;
    public string $cancelledBy;

    /**
     * Create a new message instance.
     */
    public function __construct(Booking $booking, string $cancelledBy = 'system')
    {
        $this->booking = $booking;
        $this->cancelledBy = $cancelledBy;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your SmartSpace Booking Has Been Cancelled',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.bookings.cancelled',
            with: [
                'booking' => $this->booking,
                'cancelledBy' => $this->cancelledBy,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
