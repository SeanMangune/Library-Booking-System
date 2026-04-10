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

        $qrAttachment = $this->buildQrAttachment($token);
        if ($qrAttachment === null) {
            return [];
        }

        $safeCode = preg_replace('/[^A-Za-z0-9_-]+/', '-', (string) ($this->booking->booking_code ?? $this->booking->id));
        $filename = 'booking-qr-' . trim((string) $safeCode, '-') . '.' . $qrAttachment['extension'];

        return [
            Attachment::fromData(fn () => $qrAttachment['content'], $filename)
                ->withMime($qrAttachment['mime']),
        ];
    }

    /**
     * @return array{content: string, mime: string, extension: string}|null
     */
    private function buildQrAttachment(string $token): ?array
    {
        try {
            $verifyUrl = url('/verify?token=' . $token);

            try {
                $pngResult = (new Builder())->build(
                    writer: new \Endroid\QrCode\Writer\PngWriter(),
                    data: $verifyUrl,
                    size: 480,
                    margin: 10
                );

                return [
                    'content' => $pngResult->getString(),
                    'mime' => 'image/png',
                    'extension' => 'png',
                ];
            } catch (Throwable $inner) {
                // Fall through to SVG for environments where PNG writer is unavailable.
            }

            $svgResult = (new Builder())->build(
                writer: new \Endroid\QrCode\Writer\SvgWriter(),
                data: $verifyUrl,
                size: 480,
                margin: 10
            );

            return [
                'content' => $svgResult->getString(),
                'mime' => 'image/svg+xml',
                'extension' => 'svg',
            ];
        } catch (Throwable $exception) {
            return null;
        }
    }
}
