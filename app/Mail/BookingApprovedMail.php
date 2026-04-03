<?php

namespace App\Mail;

use Endroid\QrCode\Builder\Builder;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Throwable;

use App\Models\Booking;

class BookingApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public Booking $booking;

    /**
     * Create a new message instance.
     */
    public function __construct(Booking $booking)
    {
        $this->booking = $booking;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Your SmartSpace Booking is Approved',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.bookings.approved',
            with: [
                'booking' => $this->booking,
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
        $token = (string) ($this->booking->qr_token ?? '');

        if ($token === '') {
            return [];
        }

        $png = $this->buildQrPng($token);
        if ($png === null) {
            return [];
        }

        $safeCode = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($this->booking->booking_code ?? $this->booking->id));
        $filename = 'booking-qr-' . trim((string) $safeCode, '-') . '.png';

        return [
            Attachment::fromData(fn () => $png, $filename)
                ->withMime('image/png'),
        ];
    }

    private function buildQrPng(string $token): ?string
    {
        try {
            $verifyUrl = url('/verify?token=' . $token);
            $result = (new Builder())->build(
                null,
                null,
                null,
                $verifyUrl,
                null,
                null,
                480,
                10
            );

            return $result->getString();
        } catch (Throwable $exception) {
            return null;
        }
    }
}
