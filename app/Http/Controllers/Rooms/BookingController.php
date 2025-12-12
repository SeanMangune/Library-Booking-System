<?php

namespace App\Http\Controllers\Rooms;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use Illuminate\Http\Request;
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
            'user_id' => 'nullable|exists:users,id',
            'user_name' => 'required|string|max:255',
            'user_email' => 'nullable|email',
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
            'user_email' => 'nullable|email',
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

        return response()->json(['success' => true, 'message' => 'Booking approved successfully']);
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
}
