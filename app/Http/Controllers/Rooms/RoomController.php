<?php

namespace App\Http\Controllers\Rooms;

use App\Http\Controllers\Controller;
use App\Models\Room;
use App\Models\User;
use App\Mail\RoomAddedMail;
use App\Mail\RoomStatusChangedMail;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RoomController extends Controller
{
    public function index(Request $request)
    {
        $this->syncCompletedMaintenanceWindows();

        $query = Room::query()->visible();

        if ($request->filled('capacity') && $request->capacity !== 'all') {
            $query->where('capacity', '>=', $request->capacity);
        }

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $rooms = $query->orderBy('name')->get();

        if ($request->filled('status') && $request->status !== 'all') {
            $status = (string) $request->status;
            $rooms = $rooms
                ->filter(fn (Room $room) => $room->effective_status === $status)
                ->values();
        }

        $capacities = Room::query()->visible()->distinct()->pluck('capacity')->sort()->values();

        return view('rooms.manage', compact('rooms', 'capacities'));
    }

    public function store(Request $request)
    {
        $validated = $this->validateRoomPayload($request);

        $this->ensureRoomNameAllowed($validated['name']);
        $this->ensureCollaborativeCapacityFloor($validated['name'], (int) $validated['capacity']);

        $validated['slug'] = Str::slug($validated['name']) . '-' . Str::random(5);
        $validated['location'] = '2F Library';
        $validated['requires_approval'] = $request->boolean('requires_approval');
        $validated = $this->normalizeStatusSchedulePayload($validated);

        $room = Room::create($validated);

        // Notify all users about the exciting new room!
        $users = User::where('role', 'user')->get();
        foreach ($users as $u) {
            Mail::to($u->email)->queue(new RoomAddedMail($room));
        }

        return response()->json(['success' => true, 'message' => 'Room created successfully']);
    }

    public function show(Room $room)
    {
        if ($room->status === 'maintenance' && $room->effective_status === 'operational' && $room->status_end_at && $room->status_end_at->lessThanOrEqualTo(now())) {
            $room->forceFill([
                'status' => 'operational',
                'status_start_at' => null,
                'status_end_at' => null,
            ])->saveQuietly();
            $room->refresh();
        }

        return response()->json($room);
    }

    public function update(Request $request, Room $room)
    {
        $validated = $this->validateRoomPayload($request);

        $this->ensureRoomNameAllowed($validated['name']);
        $this->ensureCollaborativeCapacityFloor($validated['name'], (int) $validated['capacity']);

        $validated['location'] = '2F Library';
        $validated['requires_approval'] = $request->boolean('requires_approval');
        $validated = $this->normalizeStatusSchedulePayload($validated, $room);

        $oldStatus = $room->effective_status;
        $room->update($validated);
        $room->refresh();

        $newStatus = $room->effective_status;
        if ($oldStatus !== $newStatus) {
            $isActive = $newStatus === 'operational';
            $wasActive = $oldStatus === 'operational';
            
            if ($isActive !== $wasActive) {
                $users = User::where('role', 'user')->get();
                $mailStatus = $isActive ? 'active' : 'inactive';
                foreach ($users as $u) {
                    Mail::to($u->email)->queue(new RoomStatusChangedMail($room, $mailStatus));
                }
            }
        }

        return response()->json(['success' => true, 'message' => 'Room updated successfully']);
    }

    private function validateRoomPayload(Request $request): array
    {
        $status = (string) $request->input('status');

        return $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:1',
            'location' => 'nullable|string|max:255',
            'status' => 'required|in:operational,maintenance,closed',
            'requires_approval' => 'boolean',
            'status_start_at' => [
                Rule::requiredIf(fn () => $status === 'maintenance'),
                'nullable',
                'date',
            ],
            'status_end_at' => [
                Rule::requiredIf(fn () => $status === 'maintenance'),
                'nullable',
                'date',
                'after_or_equal:status_start_at',
            ],
            'description' => 'nullable|string',
        ]);
    }

    private function normalizeStatusSchedulePayload(array $validated, ?Room $existingRoom = null): array
    {
        if (($validated['status'] ?? null) !== 'maintenance') {
            $validated['status_start_at'] = null;
            $validated['status_end_at'] = null;

            return $validated;
        }

        if ($existingRoom && $existingRoom->isMaintenanceOngoing()) {
            $validated['status_start_at'] = $existingRoom->status_start_at?->format('Y-m-d H:i:s');
        }

        return $validated;
    }

    private function syncCompletedMaintenanceWindows(): void
    {
        Room::query()
            ->where('status', 'maintenance')
            ->whereNotNull('status_end_at')
            ->where('status_end_at', '<=', now())
            ->update([
                'status' => 'operational',
                'status_start_at' => null,
                'status_end_at' => null,
            ]);
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

    private function ensureCollaborativeCapacityFloor(string $name, int $capacity): void
    {
        if (Str::contains(Str::lower($name), ['collaborative', 'collab']) && $capacity !== 10) {
            throw ValidationException::withMessages([
                'capacity' => 'Collaborative rooms must use a fixed base capacity of 10. Capacity 12 is only an approval extension, not the room base capacity.',
            ]);
        }
    }

    private function ensureRoomNameAllowed(string $name): void
    {
        $normalizedName = Str::of($name)->lower()->squish()->value();
        $matchesBlockedPattern = Str::contains($normalizedName, ['conference room', 'library room']);
        $looksLikeConferenceRoom = Str::contains($normalizedName, 'conference') && Str::contains($normalizedName, 'room');
        $looksLikeLibraryRoom = Str::contains($normalizedName, 'library') && Str::contains($normalizedName, 'room');

        if ($matchesBlockedPattern || $looksLikeConferenceRoom || $looksLikeLibraryRoom) {
            throw ValidationException::withMessages([
                'name' => 'This room type has been removed from the system and cannot be created or updated.',
            ]);
        }
    }
}
