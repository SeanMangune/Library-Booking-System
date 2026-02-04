<?php

namespace App\Http\Controllers\Bookings;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingAccessController extends Controller
{
    public function __invoke(Request $request, string $bookingCode)
    {
        $booking = Booking::with('room')
            ->where('booking_code', $bookingCode)
            ->first();

        if (!$booking) {
            return response()->json(['message' => 'Invalid booking QR.'], 404);
        }

        // Minimal policy: only approved/pending can “access”
        if (!in_array($booking->status, ['approved', 'pending'], true)) {
            return response()->json(['message' => 'Booking is not active.'], 403);
        }

        return response()->json([
            'booking' => [
                'id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'room_id' => $booking->room_id,
                'room_name' => $booking->room?->name,
                'date' => optional($booking->date)->format('Y-m-d') ?? $booking->date,
                'start_time' => $booking->start_time,
                'end_time' => $booking->end_time,
                'user_name' => $booking->user_name,
                'status' => $booking->status,
                'qr_code_url' => $booking->qr_code_url,
            ],
        ]);
    }
}
