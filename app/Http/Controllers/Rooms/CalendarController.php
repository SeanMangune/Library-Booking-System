<?php

namespace App\Http\Controllers\Rooms;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        $rooms = Room::orderBy('name')->get();
        $selectedRoom = $request->room_id ? Room::find($request->room_id) : $rooms->first();

        $user = $request->user();
        $verifiedRegistration = null;
        if ($user) {
            $verifiedRegistration = $user->qcidRegistration()
                ->where('verification_status', 'verified')
                ->first();
        }

        return view('rooms.calendar', compact('rooms', 'selectedRoom', 'user', 'verifiedRegistration'));
    }

    public function events(Request $request)
    {
        try {
            $query = Booking::with('room')
                ->where('status', 'approved');

            if ($request->filled('room_id')) {
                $query->where('room_id', $request->room_id);
            }

            if ($request->filled('start') && $request->filled('end')) {
                $query->whereBetween('date', [
                    Carbon::parse($request->start)->format('Y-m-d'),
                    Carbon::parse($request->end)->format('Y-m-d'),
                ]);
            }

            $bookings = $query->get();

            $user = $request->user();
            $canViewAll = $user?->isAdmin() || $user?->isSuperAdmin() || $user?->isStaff();

            return response()->json($bookings->map(function ($booking) use ($canViewAll, $user) {
                $date = $this->asCarbonDate($booking->date);

                $startTime = $this->normalizeTime($booking->start_time, '09:00:00');
                $endTime   = $this->normalizeTime($booking->end_time, '10:00:00');

                $roomName = $booking->room?->name;
                $isOwner = $user && ($booking->user_id === $user->id || $booking->user_email === $user->email);
                $canSeeDetails = $canViewAll || $isOwner;

                return [
                    'id' => $booking->id,
                    'title' => $canSeeDetails ? ($booking->title ?: ($roomName ?: 'Booking')) : 'Occupied',
                    'start' => $date->format('Y-m-d') . 'T' . $startTime,
                    'end' => $date->format('Y-m-d') . 'T' . $endTime,
                    'backgroundColor' => $this->getEventColor($booking->status),
                    'borderColor' => $this->getEventColor($booking->status),
                    'extendedProps' => [
                        'room' => $roomName,
                        'room_name' => $roomName,
                        'roomId' => $booking->room_id,
                        'purpose' => $canSeeDetails ? $booking->title : 'Occupied',
                        'attendees' => $booking->attendees,
                        'userName' => $canSeeDetails ? $booking->user_name : 'Occupied',
                        'user_name' => $canSeeDetails ? $booking->user_name : 'Occupied',
                        'status' => $booking->status,
                        'description' => $canSeeDetails ? $booking->description : '',
                        'formatted_time' => $booking->formatted_time,
                        'formatted_date' => $booking->formatted_date,
                        'date' => $date->format('M d, Y'),

                        'booking_code' => $canSeeDetails ? ($booking->booking_code ?? null) : null,
                        'qr_code_url' => $canSeeDetails ? ($booking->qr_code_url ?? null) : null,
                    ],
                ];
            }));
        } catch (\Throwable $e) {
            Log::error('CalendarController@events failed', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'trace' => config('app.debug') ? $e->getTraceAsString() : null,
            ]);

            return response()->json([
                'message' => 'Failed to load calendar events.',
            ], 500);
        }
    }

    public function dayEvents(Request $request)
    {
        try {
            $date = $request->get('date', today()->format('Y-m-d'));

            $query = Booking::with('room')
                ->where('status', 'approved')
                ->whereDate('date', $date);

            if ($request->filled('room_id')) {
                $query->where('room_id', $request->room_id);
            }

            $bookings = $query->orderBy('start_time')->get();

            $user = $request->user();
            $canViewAll = $user?->isAdmin() || $user?->isSuperAdmin() || $user?->isStaff();

            return response()->json([
                'date' => $date,
                'bookings' => $bookings->map(function ($booking) use ($canViewAll, $user) {
                    $isOwner = $user && ($booking->user_id === $user->id || $booking->user_email === $user->email);
                    $canSeeDetails = $canViewAll || $isOwner;

                    return [
                        'id' => $booking->id,
                        'purpose' => $canSeeDetails ? $booking->title : 'Occupied',
                        'title' => $canSeeDetails ? $booking->title : 'Occupied',
                        'room_name' => $booking->room?->name,
                        'room_id' => $booking->room_id,
                        'start_time' => $booking->start_time,
                        'end_time' => $booking->end_time,
                        'formatted_time' => $booking->formatted_time,
                        'formatted_date' => $booking->formatted_date,
                        'user_name' => $canSeeDetails ? $booking->user_name : 'Occupied',
                        'attendees' => $booking->attendees,
                        'status' => $booking->status,

                        'booking_code' => $canSeeDetails ? ($booking->booking_code ?? null) : null,
                        'qr_code_url' => $canSeeDetails ? ($booking->qr_code_url ?? null) : null,
                    ];
                }),
            ]);
        } catch (\Throwable $e) {
            Log::error('CalendarController@dayEvents failed', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            return response()->json([
                'message' => 'Failed to load day events.',
            ], 500);
        }
    }

    public function monthData(Request $request)
    {
        try {
            $month = $request->get('month', now()->month);
            $year = $request->get('year', now()->year);

            $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth();

            $query = Booking::with('room')
                ->where('status', 'approved')
                ->whereBetween('date', [$startOfMonth, $endOfMonth]);

            if ($request->filled('room_id')) {
                $query->where('room_id', $request->room_id);
            }

            $bookings = $query->get();

            $user = $request->user();
            $canViewAll = $user?->isAdmin() || $user?->isSuperAdmin() || $user?->isStaff();

            $grouped = $bookings->groupBy(function ($booking) {
                return $this->asCarbonDate($booking->date)->format('Y-m-d');
            })->map(function ($dayBookings) use ($canViewAll, $user) {
                return $dayBookings->map(function ($booking) use ($canViewAll, $user) {
                    $isOwner = $user && ($booking->user_id === $user->id || $booking->user_email === $user->email);
                    $canSeeDetails = $canViewAll || $isOwner;

                    return [
                        'id' => $booking->id,
                        'purpose' => $canSeeDetails ? $booking->title : 'Occupied',
                        'title' => $canSeeDetails ? $booking->title : 'Occupied',
                        'room_name' => $booking->room?->name,
                        'room_id' => $booking->room_id,
                        'start_time' => $booking->start_time,
                        'end_time' => $booking->end_time,
                        'formatted_time' => $booking->formatted_time,
                        'formatted_date' => $booking->formatted_date,
                        'user_name' => $canSeeDetails ? $booking->user_name : 'Occupied',
                        'status' => $booking->status,
                        'attendees' => $booking->attendees,

                        'booking_code' => $canSeeDetails ? ($booking->booking_code ?? null) : null,
                        'qr_code_url' => $canSeeDetails ? ($booking->qr_code_url ?? null) : null,
                    ];
                })->values();
            });

            return response()->json($grouped);
        } catch (\Throwable $e) {
            Log::error('CalendarController@monthData failed', [
                'message' => $e->getMessage(),
                'exception' => get_class($e),
            ]);

            return response()->json([
                'message' => 'Failed to load month data.',
            ], 500);
        }
    }

    private function asCarbonDate($value): Carbon
    {
        if ($value instanceof \Carbon\CarbonInterface) return Carbon::instance($value);
        if (empty($value)) return now();
        return Carbon::parse($value);
    }

    private function normalizeTime($value, string $fallback): string
    {
        $t = is_string($value) ? trim($value) : '';
        if ($t === '') return $fallback;

        // H:MM -> HH:MM:SS
        if (preg_match('/^(\d{1,2}):(\d{2})$/', $t, $m)) {
            return str_pad($m[1], 2, '0', STR_PAD_LEFT) . ':' . $m[2] . ':00';
        }

        // H:MM:SS -> HH:MM:SS
        if (preg_match('/^(\d{1,2}):(\d{2}):(\d{2})$/', $t, $m)) {
            return str_pad($m[1], 2, '0', STR_PAD_LEFT) . ':' . $m[2] . ':' . $m[3];
        }

        return $fallback;
    }

    private function getEventColor($status)
    {
        return match($status) {
            'approved' => '#10B981',
            'pending' => '#F59E0B',
            'rejected' => '#EF4444',
            'cancelled' => '#6B7280',
            default => '#3B82F6',
        };
    }
}
