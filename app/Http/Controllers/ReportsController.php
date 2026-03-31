<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Room;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        $dateFrom = trim((string) $request->input('date_from', ''));
        $dateTo = trim((string) $request->input('date_to', ''));

        if ($dateFrom !== '' && $dateTo !== '' && $dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $filters = [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'room_id' => (string) $request->input('room_id', ''),
            'status' => (string) $request->input('status', ''),
        ];

        $baseQuery = Booking::query()
            ->with('room')
            ->whereHas('room', fn ($roomQuery) => $roomQuery->visible());

        if ($filters['date_from'] !== '') {
            $baseQuery->whereDate('date', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '') {
            $baseQuery->whereDate('date', '<=', $filters['date_to']);
        }

        if ($filters['room_id'] !== '') {
            $baseQuery->where('room_id', $filters['room_id']);
        }

        if ($filters['status'] !== '') {
            $baseQuery->where('status', $filters['status']);
        }

        if ($request->input('export') === 'csv') {
            return $this->exportCsv(clone $baseQuery);
        }

        $reportBookings = (clone $baseQuery)
            ->orderByDesc('date')
            ->orderBy('start_time')
            ->get();

        $bookings = (clone $baseQuery)
            ->orderByDesc('date')
            ->orderBy('start_time')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => $reportBookings->count(),
            'approved' => $reportBookings->where('status', 'approved')->count(),
            'pending' => $reportBookings->where('status', 'pending')->count(),
            'rejected' => $reportBookings->where('status', 'rejected')->count(),
            'cancelled' => $reportBookings->where('status', 'cancelled')->count(),
            'capacity_exceptions' => $reportBookings->filter(fn (Booking $booking) => $booking->requiresCapacityPermission())->count(),
        ];

        $roomBreakdown = $reportBookings
            ->groupBy(fn (Booking $booking) => $booking->room?->name ?? 'Unknown room')
            ->map(function ($group, $roomName) {
                return [
                    'room_name' => $roomName,
                    'bookings' => $group->count(),
                    'approved' => $group->where('status', 'approved')->count(),
                    'pending' => $group->where('status', 'pending')->count(),
                    'capacity_exceptions' => $group->filter(fn (Booking $booking) => $booking->requiresCapacityPermission())->count(),
                ];
            })
            ->sortByDesc('bookings')
            ->values();

        $dailyBreakdown = $reportBookings
            ->groupBy(fn (Booking $booking) => $booking->date?->format('Y-m-d') ?? (string) $booking->date)
            ->map(function ($group, $date) {
                return [
                    'date' => $date,
                    'bookings' => $group->count(),
                    'approved' => $group->where('status', 'approved')->count(),
                    'pending' => $group->where('status', 'pending')->count(),
                ];
            })
            ->sortBy('date')
            ->values();

        $topRequesters = $reportBookings
            ->groupBy('user_name')
            ->map(fn ($group, $userName) => [
                'user_name' => $userName ?: 'Unknown user',
                'bookings' => $group->count(),
            ])
            ->sortByDesc('bookings')
            ->take(5)
            ->values();

        $rooms = Room::query()->visible()->orderBy('name')->get();

        return view('reports.index', compact(
            'bookings',
            'dailyBreakdown',
            'filters',
            'roomBreakdown',
            'rooms',
            'stats',
            'topRequesters',
        ));
    }

    private function exportCsv($query): StreamedResponse
    {
        $rows = $query
            ->orderByDesc('date')
            ->orderBy('start_time')
            ->get();

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'wb');
            fputcsv($handle, ['Date', 'Room', 'Purpose', 'Booked By', 'Email', 'Attendees', 'Status', 'Needs Capacity Permission']);

            foreach ($rows as $booking) {
                fputcsv($handle, [
                    $booking->formatted_date,
                    $booking->room?->name,
                    $booking->title,
                    $booking->user_name,
                    $booking->user_email,
                    $booking->attendees,
                    $booking->status,
                    $booking->requiresCapacityPermission() ? 'Yes' : 'No',
                ]);
            }

            fclose($handle);
        }, 'booking-report.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}