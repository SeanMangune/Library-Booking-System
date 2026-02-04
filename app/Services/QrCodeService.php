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
            // If package not installed, do nothing (don’t break booking creation).
            if (!class_exists(\SimpleSoftwareIO\QrCode\Facades\QrCode::class)) {
                Log::warning('QR package missing: simplesoftwareio/simple-qrcode');
                return;
            }

            if (!$booking->booking_code) {
                $booking->booking_code = $this->generateUniqueBookingCode();
                $booking->saveQuietly();
            }

            if ($booking->qr_code_path && Storage::disk('public')->exists($booking->qr_code_path)) {
                return;
            }

            $accessUrl = route('bookings.access', ['bookingCode' => $booking->booking_code]);

            $png = \SimpleSoftwareIO\QrCode\Facades\QrCode::format('png')
                ->size(320)
                ->margin(2)
                ->generate($accessUrl);

            $path = "qrcodes/booking_{$booking->booking_code}.png";
            Storage::disk('public')->put($path, $png);

            $booking->qr_code_path = $path;
            $booking->saveQuietly();
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
