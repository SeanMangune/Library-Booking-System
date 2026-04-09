<?php

namespace App\Http\Controllers\Rooms;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Room;
use App\Models\User;
use App\Mail\RoomAddedMail;
use App\Mail\RoomStatusChangedMail;
use App\Services\MaintenanceBookingImpactService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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

    public function update(Request $request, Room $room, MaintenanceBookingImpactService $maintenanceBookingImpactService)
    {
        $validated = $this->validateRoomPayload($request);

        $this->ensureRoomNameAllowed($validated['name']);
        $this->ensureCollaborativeCapacityFloor($validated['name'], (int) $validated['capacity']);

        $validated['location'] = '2F Library';
        $validated['requires_approval'] = $request->boolean('requires_approval');
        $validated = $this->normalizeStatusSchedulePayload($validated);

        $maintenanceStartAt = null;
        $maintenanceEndAt = null;
        $affectedBookings = collect();

        if (($validated['status'] ?? null) === 'maintenance'
            && ! empty($validated['status_start_at'])
            && ! empty($validated['status_end_at'])) {
            $maintenanceStartAt = Carbon::parse((string) $validated['status_start_at']);
            $maintenanceEndAt = Carbon::parse((string) $validated['status_end_at']);

            if ($maintenanceEndAt->greaterThan($maintenanceStartAt)) {
                $affectedBookings = $maintenanceBookingImpactService->getAffectedBookings(
                    $room,
                    $maintenanceStartAt,
                    $maintenanceEndAt,
                );
            }
        }

        $oldStatus = $room->effective_status;
        $room->update($validated);
        $room->refresh();

        $this->syncRoomBookingStatuses($room);

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

        $affectedBookingsPayload = $this->mapAffectedBookings($affectedBookings);

        $message = 'Room updated successfully';
        if ($affectedBookingsPayload->isNotEmpty()) {
            $message = sprintf(
                'Room updated. %d affected booking(s) were found. Please reschedule conflicting bookings manually.',
                $affectedBookingsPayload->count()
            );
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'affected_bookings' => $affectedBookingsPayload,
            'maintenance_window' => ($maintenanceStartAt && $maintenanceEndAt)
                ? [
                    'start_at' => $maintenanceStartAt->format('Y-m-d H:i:s'),
                    'end_at' => $maintenanceEndAt->format('Y-m-d H:i:s'),
                ]
                : null,
        ]);
    }

    public function affectedBookingsPreview(Request $request, Room $room, MaintenanceBookingImpactService $maintenanceBookingImpactService)
    {
        $validated = $request->validate([
            'status_start_at' => ['required', 'date'],
            'status_end_at' => ['required', 'date', 'after:status_start_at'],
        ]);

        $maintenanceStartAt = Carbon::parse((string) $validated['status_start_at']);
        $maintenanceEndAt = Carbon::parse((string) $validated['status_end_at']);

        $affectedBookings = $maintenanceBookingImpactService->getAffectedBookings(
            $room,
            $maintenanceStartAt,
            $maintenanceEndAt,
        );

        return response()->json([
            'success' => true,
            'affected_bookings' => $this->mapAffectedBookings($affectedBookings),
            'maintenance_window' => [
                'start_at' => $maintenanceStartAt->format('Y-m-d H:i:s'),
                'end_at' => $maintenanceEndAt->format('Y-m-d H:i:s'),
            ],
        ]);
    }

    private function mapAffectedBookings(Collection $affectedBookings): Collection
    {
        return $affectedBookings
            ->map(function (Booking $booking): array {
                return [
                    'id' => $booking->id,
                    'title' => $booking->title,
                    'status' => (string) $booking->status,
                    'user_name' => $booking->user_name,
                    'user_email' => $booking->user_email,
                    'formatted_date' => $booking->formatted_date,
                    'formatted_time' => $booking->formatted_time,
                ];
            })
            ->values();
    }

    private function syncRoomBookingStatuses(Room $room): void
    {
        if ($room->status === 'maintenance') {
            Booking::where('room_id', $room->id)
                ->update(['room_status' => 'maintenance']);

            return;
        }

        Booking::where('room_id', $room->id)
            ->where('qr_validity', 'valid')
            ->update(['room_status' => 'occupied']);

        Booking::where('room_id', $room->id)
            ->where(function ($query) {
                $query->where('qr_validity', '!=', 'valid')
                    ->orWhereNull('qr_validity');
            })
            ->update(['room_status' => 'available']);
    }

    private function validateRoomPayload(Request $request): array
    {
        $status = (string) $request->input('status');

        return $request->validate([
            'name' => 'required|string|max:255',
            'capacity' => 'required|integer|min:5|max:10',
            'location' => 'nullable|string|max:255',
            'status' => 'required|in:operational,maintenance,closed',
            'requires_approval' => 'boolean',
            'status_start_at' => [
                Rule::requiredIf(fn () => $status === 'maintenance'),
                'nullable',
                'date',
            ],
            'status_end_at' => [
                'nullable',
                'date',
                'after_or_equal:status_start_at',
            ],
            'description' => 'nullable|string',
        ]);
    }

    private function normalizeStatusSchedulePayload(array $validated): array
    {
        if (($validated['status'] ?? null) !== 'maintenance') {
            $validated['status_start_at'] = null;
            $validated['status_end_at'] = null;

            return $validated;
        }

        $selectedDate = (string) ($validated['status_start_at'] ?? '');
        $baseDate = Carbon::parse($selectedDate)->startOfDay();
        $validated['status_start_at'] = $baseDate->copy()->setTime(8, 0, 0)->format('Y-m-d H:i:s');
        $validated['status_end_at'] = $baseDate->copy()->setTime(17, 0, 0)->format('Y-m-d H:i:s');

        return $validated;
    }

    private function syncCompletedMaintenanceWindows(): void
    {
        $completedRooms = Room::query()
            ->where('status', 'maintenance')
            ->whereNotNull('status_end_at')
            ->where('status_end_at', '<=', now())
            ->get();

        /** @var Room $room */
        foreach ($completedRooms as $room) {
            $room->update([
                'status' => 'operational',
                'status_start_at' => null,
                'status_end_at' => null,
            ]);

            $this->syncRoomBookingStatuses($room);
        }
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
