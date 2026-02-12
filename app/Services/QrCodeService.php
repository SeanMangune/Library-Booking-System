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

            // Prefer verification URL (token) so scanned QR redirects to the verify page.
            $accessUrl = $booking->qr_token
                ? url('/verify?token=' . $booking->qr_token)
                : route('bookings.access', ['bookingCode' => $booking->booking_code]);

            // Build PNG using Endroid
            try {
                $builder = new \Endroid\QrCode\Builder\Builder();
                $result = $builder->build(
                    null,
                    null,
                    null,
                    $accessUrl,
                    null,
                    null,
                    320,
                    2
                );

                $png = $result->getString();
            } catch (\Throwable $inner) {
                Log::warning('Endroid QR build failed', ['message' => $inner->getMessage()]);
                return;
            }

            // Save PNG to storage for archival/legacy use but DO NOT attempt to persist a
            // non-existent `qr_code_path` column in the bookings table.
            $path = "qrcodes/booking_{$booking->booking_code}.png";
            Storage::disk('public')->put($path, $png);
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