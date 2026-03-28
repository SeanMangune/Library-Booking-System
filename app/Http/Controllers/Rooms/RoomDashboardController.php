<?php

namespace App\Http\Controllers\Rooms;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\QcIdRegistration;
use App\Models\Room;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RoomDashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = today();
        $twoWeeksAhead = today()->copy()->addDays(14);
        $user = $request->user();
        $isStaff = $user?->isAdmin() || $user?->isSuperAdmin() || $user?->isStaff();

        // Calendar data for current month
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        $calendarData = $this->getCalendarData($month, $year, $user);
        $rooms = Room::operational()->orderBy('name')->get();

        if ($isStaff) {
            return $this->adminDashboard($request, $user, $today, $twoWeeksAhead, $calendarData, $rooms);
        }

        return $this->userDashboard($request, $user, $today, $twoWeeksAhead, $calendarData, $rooms);
    }

    private function adminDashboard($request, $user, $today, $twoWeeksAhead, $calendarData, $rooms)
    {
        $collabRoomBookings = Booking::with('room')
            ->where('status', 'approved')
            ->whereBetween('date', [$today, $twoWeeksAhead])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->filter(fn ($booking) => $booking->room?->isCollaborative())
            ->values();

        $stats = [
            'pending' => Booking::where('status', 'pending')->count(),
            'approved' => Booking::where('status', 'approved')->count(),
            'rejected' => Booking::where('status', 'rejected')->count(),
            'today' => Booking::whereDate('date', $today)->where('status', 'approved')->count(),
        ];

        $pendingBookings = Booking::with('room')
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $isStaff = true;

        return view('rooms.dashboard', compact(
            'collabRoomBookings',
            'stats',
            'rooms',
            'calendarData',
            'isStaff',
            'pendingBookings',
        ));
    }

    private function userDashboard($request, $user, $today, $twoWeeksAhead, $calendarData, $rooms)
    {
        $userBookings = Booking::with('room')
            ->where('user_id', $user->id)
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->take(20)
            ->get();

        $upcomingBookings = Booking::with('room')
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->where('date', '>=', $today)
            ->orderBy('date')
            ->orderBy('start_time')
            ->take(5)
            ->get();

        $userStats = [
            'total' => Booking::where('user_id', $user->id)->count(),
            'upcoming' => $upcomingBookings->count(),
            'pending' => Booking::where('user_id', $user->id)->where('status', 'pending')->count(),
            'approved' => Booking::where('user_id', $user->id)->where('status', 'approved')->count(),
        ];

        $qcIdRegistration = QcIdRegistration::where('user_id', $user->id)->first();
        $isVerified = $qcIdRegistration !== null;

        $collabRoomBookings = Booking::with('room')
            ->where('status', 'approved')
            ->whereBetween('date', [$today, $twoWeeksAhead])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->filter(fn ($booking) => $booking->room?->isCollaborative())
            ->values();

        $stats = [
            'pending' => Booking::where('status', 'pending')->count(),
            'approved' => Booking::where('status', 'approved')->count(),
            'rejected' => Booking::where('status', 'rejected')->count(),
            'today' => Booking::whereDate('date', $today)->where('status', 'approved')->count(),
        ];

        $isStaff = false;

        return view('rooms.dashboard-user', compact(
            'collabRoomBookings',
            'stats',
            'rooms',
            'calendarData',
            'isStaff',
            'userBookings',
            'upcomingBookings',
            'userStats',
            'isVerified',
            'qcIdRegistration',
        ));
    }

    private function getCalendarData($month, $year, $user = null)
    {
        $canViewAll = $user?->isAdmin() || $user?->isSuperAdmin() || $user?->isStaff();

        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $bookings = Booking::with('room')
            ->whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('status', 'approved')
            ->get();

        return $bookings->groupBy(function($booking) {
            return $booking->date->format('Y-m-d');
        })->map(function($dayBookings) use ($canViewAll) {
            return $dayBookings->map(function($booking) use ($canViewAll) {
                return [
                    'id' => $booking->id,
                    'title' => $canViewAll ? ($booking->title ?: $booking->user_name) : 'Occupied',
                    'purpose' => $canViewAll ? $booking->title : 'Occupied',
                    'room_name' => $booking->room->name,
                    'start_time' => $booking->start_time,
                    'end_time' => $booking->end_time,
                    'formatted_time' => $booking->formatted_time,
                    'user_name' => $canViewAll ? $booking->user_name : 'Occupied',
                    'status' => $booking->status,
                ];
            })->values();
        });
    }

    public function calendarData(Request $request)
    {
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);

        return response()->json($this->getCalendarData($month, $year, $request->user()));
    }
}
