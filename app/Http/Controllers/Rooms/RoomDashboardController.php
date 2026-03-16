<?php

namespace App\Http\Controllers\Rooms;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RoomDashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = today();
        $twoWeeksAhead = today()->copy()->addDays(14);

        $todayReservations = Booking::with('room')
            ->where('status', 'approved')
            ->whereDate('date', $today)
            ->orderBy('start_time')
            ->get();

        $upcomingReservations = Booking::with('room')
            ->where('status', 'approved')
            ->whereDate('date', '>', $today)
            ->orderBy('date')
            ->orderBy('start_time')
            ->limit(30)
            ->get();

        // Collaborative-room bookings from today to the next two weeks.
        $collabRoomBookings = Booking::with('room')
            ->where('status', 'approved')
            ->whereBetween('date', [$today, $twoWeeksAhead])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->filter(function ($booking) {
                return $booking->room?->isCollaborative();
            })
            ->values();

        // Stats
        $stats = [
            'pending' => Booking::where('status', 'pending')->count(),
            'approved' => Booking::where('status', 'approved')->count(),
            'rejected' => Booking::where('status', 'rejected')->count(),
            'today' => Booking::whereDate('date', $today)->where('status', 'approved')->count(),
        ];

        $rooms = Room::operational()->orderBy('name')->get();

        // Calendar data for current month
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        $calendarData = $this->getCalendarData($month, $year);

        return view('rooms.dashboard', compact(
            'todayReservations',
            'upcomingReservations',
            'collabRoomBookings',
            'stats',
            'rooms',
            'calendarData',
        ));
    }

    private function getCalendarData($month, $year)
    {
        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $bookings = Booking::with('room')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('status', 'approved')
            ->get();

        return $bookings->groupBy(function($booking) {
            return $booking->date->format('Y-m-d');
        })->map(function($dayBookings) {
            return $dayBookings->map(function($booking) {
                return [
                    'id' => $booking->id,
                    'title' => $booking->title ?: $booking->user_name,
                    'purpose' => $booking->title,
                    'room_name' => $booking->room->name,
                    'start_time' => $booking->start_time,
                    'end_time' => $booking->end_time,
                    'formatted_time' => $booking->formatted_time,
                    'user_name' => $booking->user_name,
                    'status' => $booking->status,
                ];
            })->values();
        });
    }

    public function calendarData(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        return response()->json($this->getCalendarData($month, $year));
    }
}
