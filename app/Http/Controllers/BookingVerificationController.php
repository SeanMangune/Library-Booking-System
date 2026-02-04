<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

class BookingVerificationController extends Controller
{
    public function verify(string $bookingCode)
    {
        $booking = Booking::where('booking_code', $bookingCode)
            ->with('room')
            ->first();

        if (!$booking) {
            return response()->json([
                'valid' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        return response()->json([
            'valid' => true,
            'booking' => [
                'id' => $booking->id,
                'booking_code' => $booking->booking_code,
                'title' => $booking->title,
                'room_name' => $booking->room->name,
                'date' => $booking->date->format('M d, Y'),
                'start_time' => $booking->start_time,
                'end_time' => $booking->end_time,
                'user_name' => $booking->user_name,
                'status' => $booking->status,
                'attendees' => $booking->attendees,
            ],
        ]);
    }

    public function showQrCode(Booking $booking)
    {
        if (!$booking->qr_code_path) {
            app(\App\Services\QrCodeService::class)->generateForBooking($booking);
            $booking->refresh();
        }

        return view('bookings.qr-code', compact('booking'));
    }
}
