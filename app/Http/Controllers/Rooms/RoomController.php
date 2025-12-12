<?php

namespace App\Http\Controllers\Rooms;

use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $query = Room::query();

        // Apply filters
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('capacity') && $request->capacity !== 'all') {
            $query->where('capacity', '>=', $request->capacity);
        }

        if ($request->filled('location') && $request->location !== 'all') {
            $query->where('location', $request->location);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $rooms = $query->orderBy('name')->get();
        $locations = Room::distinct()->pluck('location')->filter()->values();
        $capacities = Room::distinct()->pluck('capacity')->sort()->values();

        return view('rooms.manage', compact('rooms', 'locations', 'capacities'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'location' => 'nullable|string|max:255',
            'status' => 'required|in:operational,maintenance,closed',
            'requires_approval' => 'boolean',
            'status_start_at' => 'nullable|date',
            'status_end_at' => 'nullable|date|after_or_equal:status_start_at',
            'description' => 'nullable|string',
        ]);

        $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(5);
        $validated['requires_approval'] = $request->boolean('requires_approval');

        Room::create($validated);

        return response()->json(['success' => true, 'message' => 'Room created successfully']);
    }

    public function show(Room $room)
    {
        return response()->json($room);
    }

    public function update(Request $request, Room $room)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'location' => 'nullable|string|max:255',
            'status' => 'required|in:operational,maintenance,closed',
            'requires_approval' => 'boolean',
            'status_start_at' => 'nullable|date',
            'status_end_at' => 'nullable|date|after_or_equal:status_start_at',
            'description' => 'nullable|string',
        ]);

        $validated['requires_approval'] = $request->boolean('requires_approval');

        $room->update($validated);

        return response()->json(['success' => true, 'message' => 'Room updated successfully']);
    }

    public function destroy(Room $room)
    {
        // Check if room has any bookings
        if ($room->bookings()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete room with existing bookings'
            ], 422);
        }

        $room->delete();

        return response()->json(['success' => true, 'message' => 'Room deleted successfully']);
    }
}
