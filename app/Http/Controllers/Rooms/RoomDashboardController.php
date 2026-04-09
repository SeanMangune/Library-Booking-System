<?php

namespace App\Http\Controllers\Rooms;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\QcIdRegistration;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RoomDashboardController extends Controller
{
    public function index(Request $request)
    {
        $today = today();
        $twoWeeksAhead = today()->copy()->addDays(14);
        $user = $request->user();
        $classification = $user?->classification() ?? User::CLASSIFICATION_STUDENT;
        $isAdminClassification = $classification === User::CLASSIFICATION_ADMIN
            || $user?->isAdmin()
            || $user?->isSuperAdmin();

        // Calendar data for current month
        $month = $request->get('month', now()->month);
        $year = $request->get('year', now()->year);
        $calendarData = $this->getCalendarData($month, $year, $user);
        $rooms = Room::query()->visible()->operational()->orderBy('name')->get();

        if ($isAdminClassification) {
            return $this->adminDashboard($request, $user, $today, $twoWeeksAhead, $calendarData, $rooms, $classification);
        }

        return $this->userDashboard($request, $user, $today, $twoWeeksAhead, $calendarData, $rooms, $classification);
    }

    private function adminDashboard($request, $user, $today, $twoWeeksAhead, $calendarData, $rooms, string $classification)
    {
        $collabRoomBookings = Booking::with('room')
            ->whereHas('room', fn ($roomQuery) => $roomQuery->visible())
            ->where('status', 'approved')
            ->whereBetween('date', [$today, $twoWeeksAhead])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->filter(fn ($booking) => $booking->room?->isCollaborative())
            ->values();

        $stats = [
            'pending' => Booking::whereHas('room', fn ($roomQuery) => $roomQuery->visible())->pendingActive()->count(),
            'approved' => Booking::whereHas('room', fn ($roomQuery) => $roomQuery->visible())->where('status', 'approved')->count(),
            'rejected' => Booking::whereHas('room', fn ($roomQuery) => $roomQuery->visible())->where('status', 'rejected')->count(),
            'today' => Booking::whereHas('room', fn ($roomQuery) => $roomQuery->visible())->whereDate('date', $today)->where('status', 'approved')->count(),
        ];

        $pendingBookingsList = Booking::with('room', 'user')
            ->whereHas('room', fn ($roomQuery) => $roomQuery->visible())
            ->pendingActive()
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $approvedBookingsList = Booking::with('room', 'user')
            ->whereHas('room', fn ($roomQuery) => $roomQuery->visible())
            ->where('status', 'approved')
            ->orderBy('date', 'desc')
            ->take(10)
            ->get();

        $rejectedBookingsList = Booking::with('room', 'user')
            ->whereHas('room', fn ($roomQuery) => $roomQuery->visible())
            ->where('status', 'rejected')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        $todayBookingsList = Booking::with('room', 'user')
            ->whereHas('room', fn ($roomQuery) => $roomQuery->visible())
            ->whereDate('date', $today)
            ->where('status', 'approved')
            ->orderBy('start_time')
            ->take(10)
            ->get();

        $isStaff = true;
        $dashboardAudience = User::CLASSIFICATION_ADMIN;

        $pendingBookings = $pendingBookingsList;

        return view('rooms.dashboard', compact(
            'collabRoomBookings',
            'stats',
            'rooms',
            'calendarData',
            'isStaff',
            'pendingBookings',
            'pendingBookingsList',
            'approvedBookingsList',
            'rejectedBookingsList',
            'todayBookingsList',
            'classification',
            'dashboardAudience',
        ));
    }

    private function userDashboard($request, $user, $today, $twoWeeksAhead, $calendarData, $rooms, string $classification)
    {
        $dashboardReference = now((string) config('app.booking_timezone', 'Asia/Manila'));
        $dashboardDate = $dashboardReference->toDateString();
        $dashboardTime = $dashboardReference->format('H:i:s');

        $activeDashboardWindow = function ($query) use ($dashboardDate, $dashboardTime) {
            $query
                ->whereDate('date', '>', $dashboardDate)
                ->orWhere(function ($todayQuery) use ($dashboardDate, $dashboardTime) {
                    $todayQuery
                        ->whereDate('date', '=', $dashboardDate)
                        ->where(function ($timeQuery) use ($dashboardTime) {
                            $timeQuery
                                ->whereNull('end_time')
                                ->orWhereTime('end_time', '>=', $dashboardTime);
                        });
                });
        };

        $dashboardUserBookingsQuery = Booking::with('room')
            ->whereHas('room', fn ($roomQuery) => $roomQuery->visible())
            ->where('user_id', $user->id)
            ->where($activeDashboardWindow);

        $userBookings = (clone $dashboardUserBookingsQuery)
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->take(20)
            ->get();

        $upcomingBookings = Booking::with('room')
            ->whereHas('room', fn ($roomQuery) => $roomQuery->visible())
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->where($activeDashboardWindow)
            ->orderBy('date')
            ->orderBy('start_time')
            ->take(5)
            ->get();

        $pendingUserBookingsQuery = Booking::query()
            ->whereHas('room', fn ($roomQuery) => $roomQuery->visible())
            ->where('user_id', $user->id)
            ->pendingActive($dashboardReference);

        $userStats = [
            'total' => (clone $dashboardUserBookingsQuery)->count(),
            'upcoming' => $upcomingBookings->count(),
            'pending' => (clone $pendingUserBookingsQuery)->count(),
            'approved' => Booking::whereHas('room', fn ($roomQuery) => $roomQuery->visible())
                ->where('user_id', $user->id)
                ->where('status', 'approved')
                ->where($activeDashboardWindow)
                ->count(),
        ];

        $allUserBookings = (clone $dashboardUserBookingsQuery)
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get();
        $userStatsBookings = [
            'total' => $allUserBookings,
            'upcoming' => $upcomingBookings,
            'pending' => $allUserBookings->filter(fn($b) => $b->status === 'pending')->values(),
            'approved' => $allUserBookings->filter(fn($b) => $b->status === 'approved')->values(),
        ];

        $qcIdRegistration = QcIdRegistration::where('user_id', $user->id)->first();
        $isVerified = $qcIdRegistration !== null && $qcIdRegistration->verification_status === 'verified';

        $collabRoomBookings = Booking::with('room')
            ->whereHas('room', fn ($roomQuery) => $roomQuery->visible())
            ->where('status', 'approved')
            ->whereBetween('date', [$today, $twoWeeksAhead])
            ->orderBy('date')
            ->orderBy('start_time')
            ->get()
            ->filter(fn ($booking) => $booking->room?->isCollaborative())
            ->values();

        $stats = [
            'pending' => Booking::whereHas('room', fn ($roomQuery) => $roomQuery->visible())->pendingActive()->count(),
            'approved' => Booking::whereHas('room', fn ($roomQuery) => $roomQuery->visible())->where('status', 'approved')->count(),
            'rejected' => Booking::whereHas('room', fn ($roomQuery) => $roomQuery->visible())->where('status', 'rejected')->count(),
            'today' => Booking::whereHas('room', fn ($roomQuery) => $roomQuery->visible())->whereDate('date', $today)->where('status', 'approved')->count(),
        ];

        $isStaff = false;
        $dashboardAudience = in_array($classification, [User::CLASSIFICATION_FACULTY, User::CLASSIFICATION_STUDENT], true)
            ? $classification
            : User::CLASSIFICATION_STUDENT;

        return view('rooms.dashboard-user', compact(
            'collabRoomBookings',
            'stats',
            'rooms',
            'calendarData',
            'isStaff',
            'userBookings',
            'upcomingBookings',
            'userStats',
            'userStatsBookings',
            'isVerified',
            'qcIdRegistration',
            'classification',
            'dashboardAudience',
        ));
    }

    private function getCalendarData($month, $year, $user = null)
    {
        $canViewAll = $user?->isAdmin() || $user?->isSuperAdmin() || $user?->isStaff();

        $startOfMonth = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endOfMonth = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $bookings = Booking::with('room')
            ->whereHas('room', fn ($roomQuery) => $roomQuery->visible())
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
                    'room_location' => $booking->room->location ?? null,
                    'start_time' => $booking->start_time,
                    'end_time' => $booking->end_time,
                    'formatted_time' => $booking->formatted_time,
                    'formatted_date' => $booking->formatted_date,
                    'user_name' => $canViewAll ? $booking->user_name : 'Occupied',
                    'user_email' => $canViewAll ? $booking->user_email : null,
                    'attendees' => $booking->attendees,
                    'status' => $booking->status,
                    'booking_status' => $booking->booking_status ?? $booking->determineBookingStatus(),
                    'qr_token' => $booking->qr_token,
                    'qr_code_url' => $booking->qr_code_url,
                    'description' => $canViewAll ? $booking->description : null,
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
