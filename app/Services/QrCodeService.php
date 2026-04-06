<?php

namespace App\Services;

use App\Models\Booking;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class QrCodeService
{
    public function ensureBookingQr(Booking $booking): void
    {
        try {
            // Prefer Endroid QR builder for reliable, scannable PNGs.
            if (! class_exists(\Endroid\QrCode\Builder\Builder::class)) {
                Log::warning('QR package missing: endroid/qr-code');
                return;
            }

            if (! $booking->booking_code) {
                $booking->booking_code = $this->generateUniqueBookingCode();
                $booking->saveQuietly();
            }

            // Use plain qr_token or booking_code for the QR payload
            $payload = $booking->qr_token
                ? $booking->qr_token
                : $booking->booking_code;

            // Build PNG using Endroid
            try {
                $builder = new \Endroid\QrCode\Builder\Builder();
                $result = $builder->build(
                    writer: new \Endroid\QrCode\Writer\SvgWriter(),
                    data: $payload,
                    size: 320,
                    margin: 2
                );

                $svg = $result->getString();
                $content = $svg;
            } catch (\Throwable $inner) {
                Log::warning('Endroid QR build failed', ['message' => $inner->getMessage()]);
                return;
            }

            // Save SVG to storage for archival/legacy use but DO NOT attempt to persist a
            // non-existent `qr_code_path` column in the bookings table.
            $path = "qrcodes/booking_{$booking->booking_code}.svg";
            Storage::disk('public')->put($path, $content);
        } catch (\Throwable $e) {
            Log::error('QR generation failed', [
                'booking_id' => $booking->id ?? null,
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);
        }
    }

    private function generateUniqueBookingCode(): string
    {
        do {
            $code = 'BK-' . now()->format('Ymd') . '-' . Str::upper(Str::random(8));
        } while (Booking::where('booking_code', $code)->exists());

        return $code;
    }
}