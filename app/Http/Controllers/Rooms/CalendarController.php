<?php

namespace App\Http\Controllers\Rooms;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\Request;
use Carbon\Carbon;

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

        return response()->json($bookings->map(function($booking) {
            $startTime = $booking->start_time ?? '09:00';
            $endTime = $booking->end_time ?? '10:00';
            
            // Ensure time format is correct (HH:MM)
            if (strlen($startTime) === 5) {
                $startTime .= ':00';
            }
            if (strlen($endTime) === 5) {
                $endTime .= ':00';
            }
            
            return [
                'id' => $booking->id,
                'title' => $booking->title ?: $booking->room->name,
                'start' => $booking->date->format('Y-m-d') . 'T' . $startTime,
                'end' => $booking->date->format('Y-m-d') . 'T' . $endTime,
                'backgroundColor' => $this->getEventColor($booking->status),
                'borderColor' => $this->getEventColor($booking->status),
                'extendedProps' => [
                    'room' => $booking->room->name,
                    'room_name' => $booking->room->name,
                    'roomId' => $booking->room_id,
                    'attendees' => $booking->attendees,
                    'userName' => $booking->user_name,
                    'user_name' => $booking->user_name,
                    'status' => $booking->status,
                    'description' => $booking->description,
                    'formatted_time' => $booking->formatted_time,
                    'date' => $booking->date->format('M d, Y'),
                ],
            ];
        }));
    }

    public function dayEvents(Request $request)
    {
        $date = $request->get('date', today()->format('Y-m-d'));

        $query = Booking::with('room')
            ->whereDate('date', $date);

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        $bookings = $query->orderBy('start_time')->get();

        return response()->json([
            'date' => $date,
            'bookings' => $bookings->map(function($booking) {
                return [
                    'id' => $booking->id,
                    'title' => $booking->title,
                    'room_name' => $booking->room->name,
                    'room_id' => $booking->room_id,
                    'start_time' => $booking->start_time,
                    'end_time' => $booking->end_time,
                    'formatted_time' => $booking->formatted_time,
                    'user_name' => $booking->user_name,
                    'attendees' => $booking->attendees,
                    'status' => $booking->status,
                ];
            }),
        ]);
    }

    public function monthData(Request $request)
    {
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

        // Group bookings by date
        $grouped = $bookings->groupBy(function($booking) {
            return $booking->date->format('Y-m-d');
        })->map(function($dayBookings) {
            return $dayBookings->map(function($booking) {
                return [
                    'id' => $booking->id,
                    'title' => $booking->title,
                    'room_name' => $booking->room->name,
                    'room_id' => $booking->room_id,
                    'start_time' => $booking->start_time,
                    'end_time' => $booking->end_time,
                    'formatted_time' => $booking->formatted_time,
                    'user_name' => $booking->user_name,
                    'status' => $booking->status,
                    'attendees' => $booking->attendees,
                ];
            })->values();
        });

        return response()->json($grouped);
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
