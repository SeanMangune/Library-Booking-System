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

        return view('rooms.calendar', compact('rooms', 'selectedRoom'));
    }

    public function events(Request $request)
    {
        try {
            $query = Booking::with('room')
                ->whereIn('status', ['approved', 'pending']);

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

            return response()->json($bookings->map(function ($booking) {
                $date = $this->asCarbonDate($booking->date);

                $startTime = $this->normalizeTime($booking->start_time, '09:00:00');
                $endTime   = $this->normalizeTime($booking->end_time, '10:00:00');

                $roomName = $booking->room?->name;

                return [
                    'id' => $booking->id,
                    'title' => $booking->title ?: ($roomName ?: 'Booking'),
                    'start' => $date->format('Y-m-d') . 'T' . $startTime,
                    'end' => $date->format('Y-m-d') . 'T' . $endTime,
                    'backgroundColor' => $this->getEventColor($booking->status),
                    'borderColor' => $this->getEventColor($booking->status),
                    'extendedProps' => [
                        'room' => $roomName,
                        'room_name' => $roomName,
                        'roomId' => $booking->room_id,
                        'attendees' => $booking->attendees,
                        'userName' => $booking->user_name,
                        'user_name' => $booking->user_name,
                        'status' => $booking->status,
                        'description' => $booking->description,
                        // safer than relying on an accessor that may throw
                        // ensure human-friendly date/time values are present
                        'formatted_time' => $booking->formatted_time,
                        'formatted_date' => $booking->formatted_date,
                        'date' => $date->format('M d, Y'),

                        'booking_code' => $booking->booking_code ?? null,
                        'qr_code_url' => $booking->getAttribute('qr_code_url') ?? null,
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
                ->whereDate('date', $date);

            if ($request->filled('room_id')) {
                $query->where('room_id', $request->room_id);
            }

            $bookings = $query->orderBy('start_time')->get();

            return response()->json([
                'date' => $date,
                'bookings' => $bookings->map(function ($booking) {
                    return [
                        'id' => $booking->id,
                        'title' => $booking->title,
                        'room_name' => $booking->room?->name,
                        'room_id' => $booking->room_id,
                        'start_time' => $booking->start_time,
                        'end_time' => $booking->end_time,
                        'formatted_time' => $booking->formatted_time,
                        'formatted_date' => $booking->formatted_date,
                        'user_name' => $booking->user_name,
                        'attendees' => $booking->attendees,
                        'status' => $booking->status,

                        'booking_code' => $booking->booking_code ?? null,
                        'qr_code_url' => $booking->getAttribute('qr_code_url') ?? null,
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
                ->whereBetween('date', [$startOfMonth, $endOfMonth]);

            if ($request->filled('room_id')) {
                $query->where('room_id', $request->room_id);
            }

            $bookings = $query->get();

            $grouped = $bookings->groupBy(function ($booking) {
                return $this->asCarbonDate($booking->date)->format('Y-m-d');
            })->map(function ($dayBookings) {
                return $dayBookings->map(function ($booking) {
                    return [
                        'id' => $booking->id,
                        'title' => $booking->title,
                        'room_name' => $booking->room?->name,
                        'room_id' => $booking->room_id,
                        'start_time' => $booking->start_time,
                        'end_time' => $booking->end_time,
                        'formatted_time' => $booking->formatted_time,
                        'formatted_date' => $booking->formatted_date,
                        'user_name' => $booking->user_name,
                        'status' => $booking->status,
                        'attendees' => $booking->attendees,

                        'booking_code' => $booking->booking_code ?? null,
                        'qr_code_url' => $booking->getAttribute('qr_code_url') ?? null,
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
