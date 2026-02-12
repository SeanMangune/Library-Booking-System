<?php

namespace App\Http\Controllers\Rooms;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        $query = Booking::with('room');

        // Apply filters
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('room') && $request->room !== 'all') {
            $query->where('room_id', $request->room);
        }

        if ($request->filled('time_period')) {
            $query->byTimePeriod($request->time_period);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%")
                  ->orWhereHas('room', function($rq) use ($search) {
                      $rq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $bookings = $query->orderByDesc('date')->orderBy('start_time')->paginate(15);
        $rooms = Room::orderBy('name')->get();

        return view('rooms.reservations', compact('bookings', 'rooms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'room_id' => 'required|exists:rooms,id',
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'attendees' => 'required|integer|min:1',
            'user_id' => 'nullable|exists:bookings,id',
            'user_name' => 'nullable|string|max:255',
            'user_email' => 'nullable|email|max:255',
            'description' => 'nullable|string',
        ]);

        $room = Room::findOrFail($validated['room_id']);

        // Check for time conflicts
        $hasConflict = Booking::where('room_id', $validated['room_id'])
            ->where('date', $validated['date'])
            ->where('status', 'approved')
            ->where(function($query) use ($validated) {
                $query->whereBetween('start_time', [$validated['start_time'], $validated['end_time']])
                    ->orWhereBetween('end_time', [$validated['start_time'], $validated['end_time']])
                    ->orWhere(function($q) use ($validated) {
                        $q->where('start_time', '<=', $validated['start_time'])
                          ->where('end_time', '>=', $validated['end_time']);
                    });
            })
            ->exists();

        if ($hasConflict) {
            return response()->json([
                'success' => false,
                'message' => 'This time slot conflicts with an existing booking'
            ], 422);
        }

        // Set initial status based on room settings
        $validated['status'] = $room->requires_approval ? 'pending' : 'approved';
        $validated['time'] = Carbon::parse($validated['start_time'])->format('g:i A');
        
        // Calculate duration
        $startTime = Carbon::parse($validated['start_time']);
        $endTime = Carbon::parse($validated['end_time']);
        $durationMinutes = $startTime->diffInMinutes($endTime);
        $hours = floor($durationMinutes / 60);
        $minutes = $durationMinutes % 60;
        $validated['duration'] = $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";

        $booking = Booking::create($validated);

        return response()->json([
            'success' => true,
            'message' => $room->requires_approval 
                ? 'Booking submitted for approval' 
                : 'Booking confirmed successfully',
            'booking' => $booking->load('room')
        ]);
    }

    public function show(Booking $booking)
    {
        return response()->json($booking->load('room'));
    }

    public function update(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'room_id' => 'required|exists:rooms,id',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'attendees' => 'required|integer|min:1',
            'user_name' => 'required|string|max:255',
            'user_email' => 'required|email|max:255',
            'description' => 'nullable|string',
        ]);

        $booking->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Booking updated successfully',
            'booking' => $booking->load('room')
        ]);
    }

    public function destroy(Booking $booking)
    {
        $booking->delete();

        return response()->json(['success' => true, 'message' => 'Booking deleted successfully']);
    }

    public function cancel(Booking $booking)
    {
        $booking->update(['status' => 'cancelled']);

        return response()->json(['success' => true, 'message' => 'Booking cancelled successfully']);
    }

    public function approve(Booking $booking, Request $request)
    {
        $booking->update([
            'status' => 'approved',
            'reason' => $request->get('reason'),
        ]);

        // --- Ensure a unique qr_token exists for this booking ---
        if (! $booking->qr_token) {
            // try a few times to avoid collision (extremely unlikely)
            for ($i = 0; $i < 5; $i++) {
                $token = (string) \Illuminate\Support\Str::uuid();
                if (! Booking::where('qr_token', $token)->exists()) {
                    $booking->qr_token = $token;
                    $booking->saveQuietly();
                    break;
                }
            }
        }

        // Keep legacy QR file generation (if service installed)
        try {
            app(\App\Services\QrCodeService::class)->ensureBookingQr($booking);
        } catch (\Throwable $e) {
            // swallow - QR service is optional
        }

        // Prepare response payload and include an inline Endroid PNG (base64) so the
        // frontend can render the QR immediately without a second request.
        $fresh = $booking->fresh()->load('room');
        $qrDataUri = null;

        if ($booking->qr_token) {
            try {
                $verifyUrl = url('/verify?token=' . $booking->qr_token);
                $builder = new \Endroid\QrCode\Builder\Builder();
                $result = $builder->build(
                    null, // writer
                    null, // writer options
                    null, // validate result
                    $verifyUrl, // data
                    null, // encoding
                    null, // error correction
                    480, // size
                    10 // margin
                );

                $png = $result->getString();
                $qrDataUri = 'data:image/png;base64,' . base64_encode($png);
            } catch (\Throwable $e) {
                // fallback: leave qrDataUri null (frontend can still use qr_code_url)
                $qrDataUri = null;
            }
        }

        $payload = $fresh->toArray();
        // Prefer inline PNG data (Endroid) when available, otherwise use stored/public URL
        $payload['qr_code_data'] = $qrDataUri;
        $payload['qr_code_url'] = $qrDataUri ?? $fresh->getAttribute('qr_code_url') ?? null;

        return response()->json([
            'success' => true,
            'message' => 'Booking approved successfully',
            'booking' => $payload,
        ]);
    }

    public function reject(Booking $booking, Request $request)
    {
        $booking->update([
            'status' => 'rejected',
            'reason' => $request->get('reason'),
        ]);

        return response()->json(['success' => true, 'message' => 'Booking rejected successfully']);
    }

    public function approvals(Request $request)
    {
        $query = Booking::with('room')->where('status', 'pending');

        if ($request->filled('room') && $request->room !== 'all') {
            $query->where('room_id', $request->room);
        }

        $bookings = $query->orderBy('date')->orderBy('start_time')->paginate(15);
        $rooms = Room::orderBy('name')->get();

        $stats = [
            'pending' => Booking::where('status', 'pending')->count(),
            'approved' => Booking::where('status', 'approved')->count(),
            'rejected' => Booking::where('status', 'rejected')->count(),
        ];

        return view('rooms.approvals', compact('bookings', 'rooms', 'stats'));
    }

    /**
     * Return a PNG QR image for the provided booking qr_token.
     * Uses an external QR generator as a reliable fallback so the image is always scannable.
     * Supports ?download=1 to return Content-Disposition: attachment
     */
    public function qrImage(Request $request, string $token)
    {
        $booking = Booking::where('qr_token', $token)->first();

        if (! $booking) {
            return response('Not found', 404);
        }

        // Build a public verification URL directly (route name may not exist in all installs)
        $verifyUrl = url('/verify?token=' . $token);

        try {
            // Use Endroid to generate a scannable PNG in-app
            $builder = new \Endroid\QrCode\Builder\Builder();
            $result = $builder->build(
                null, // writer
                null, // writer options
                null, // validate result
                $verifyUrl, // data
                null, // encoding
                null, // error correction
                480, // size
                10 // margin
            );

            $png = $result->getString();

            $headers = ['Content-Type' => 'image/png'];
            if ($request->query('download')) {
                $headers['Content-Disposition'] = 'attachment; filename="booking-' . $token . '.png"';
            }

            return response($png, 200, $headers);
        } catch (\Throwable $e) {
            // Fallback to external QR generator if Endroid fails for any reason
            try {
                $external = 'https://api.qrserver.com/v1/create-qr-code/?size=480x480&data=' . urlencode($verifyUrl);
                $resp = \Illuminate\Support\Facades\Http::get($external);
                $content = $resp->body();
                $headers = ['Content-Type' => 'image/png'];
                if ($request->query('download')) {
                    $headers['Content-Disposition'] = 'attachment; filename="booking-' . $token . '.png"';
                }
                return response($content, 200, $headers);
            } catch (\Throwable $e) {
                // Last-resort: return fallback placeholder (keeps <img> from breaking)
                if (file_exists(resource_path('images/qr-fallback.png'))) {
                    return response()->file(resource_path('images/qr-fallback.png'));
                }

                return response('QR generation unavailable', 500);
            }
        }
    }

    /**
     * Public verification page for scanned QR tokens
     */
    public function verify(Request $request)
    {
        $token = $request->query('token');
        $booking = Booking::where('qr_token', $token)->with('room')->first();

        if (! $booking) {
            return view('rooms.verify', ['booking' => null, 'token' => $token]);
        }

        return view('rooms.verify', ['booking' => $booking, 'token' => $token]);
    }
}
