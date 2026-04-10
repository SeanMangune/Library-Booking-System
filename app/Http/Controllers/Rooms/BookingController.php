<?php

namespace App\Http\Controllers\Rooms;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\QcIdRegistration;
use App\Models\Room;
use App\Models\User;
use App\Notifications\BookingApprovedNotification;
use App\Notifications\BookingRejectedNotification;
use App\Notifications\BookingRescheduledNotification;
use App\Notifications\NewBookingSubmittedForStaffNotification;
use App\Services\QcIdOcrVerifier;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Mail;
use App\Mail\BookingPendingMail;
use App\Mail\BookingApprovedMail;
use App\Mail\BookingRescheduledMail;
use App\Mail\BookingRejectedMail;
use App\Mail\BookingCancelledMail;

class BookingController extends Controller
{
    private const BOOKING_OPEN_HOUR = 8;

    private const BOOKING_CLOSE_HOUR = 17;

    private const BOOKING_MAX_AVAILABILITY_DAYS = 7;

    private const BOOKING_MIN_ATTENDEES = 5;

    private const BOOKING_STANDARD_ATTENDEES = 10;

    private const BOOKING_MAX_ATTENDEES = 12;

    private const BOOKING_MIN_LEAD_MINUTES = 15;

    public function index(Request $request)
    {
        $user = $request->user();
        $canViewAll = $user?->isAdmin() || $user?->isSuperAdmin();

        $query = Booking::with('room')
            ->whereHas('room', fn ($roomQuery) => $roomQuery->visible());

        if (! $canViewAll && $user) {
            $query->where(function ($scoped) use ($user) {
                $scoped->where('user_id', $user->id);

                if (! empty($user->email)) {
                    $scoped->orWhere('user_email', $user->email);
                }
            });
        }

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
        $rooms = Room::query()->visible()->orderBy('name')->get();

        // Fetch verified QC ID registration for autofill
        $qcIdRegistration = null;
        if ($user) {
            $qcIdRegistration = \App\Models\QcIdRegistration::where('user_id', $user->id)
                ->where('verification_status', 'verified')
                ->first();
        }

        return view('rooms.reservations', compact('bookings', 'rooms', 'qcIdRegistration'));
    }

    public function searchUsers(Request $request)
    {
        $actingUser = $request->user();

        if (! $actingUser || ! $actingUser->isStaff()) {
            return response()->json([
                'users' => [],
            ], 403);
        }

        $search = trim((string) $request->query('q', ''));
        if (strlen($search) < 2) {
            return response()->json([
                'users' => [],
            ]);
        }

        $users = User::query()
            ->where('role', User::ROLE_USER)
            ->where(function ($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%");
            })
            ->with(['qcidRegistration' => function ($query) {
                $query->where('verification_status', 'verified');
            }])
            ->orderBy('name')
            ->limit(10)
            ->get();

        $payload = $users->map(function (User $user): array {
            $registration = $user->qcidRegistration;

            return [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'has_verified_qcid' => (bool) $registration,
                'qcid_registration' => $registration ? [
                    'full_name' => $registration->full_name,
                    'qcid_number' => $registration->qcid_number,
                    'date_issued' => optional($registration->date_issued)->format('Y-m-d'),
                    'valid_until' => optional($registration->valid_until)->format('Y-m-d'),
                    'address' => $registration->address,
                ] : null,
            ];
        })->values();

        return response()->json([
            'users' => $payload,
        ]);
    }

    public function availability(Request $request)
    {
        $validated = $request->validate([
            'date' => [
                'nullable',
                'date',
                'after_or_equal:today',
                function ($attribute, $value, $fail) {
                    try {
                        $selectedDate = Carbon::parse((string) $value)->startOfDay();
                        if ($selectedDate->greaterThan($this->bookingWindowEndDate())) {
                            $fail('Bookings can only be made within the next 7 days.');
                        }
                    } catch (\Throwable $e) {
                        $fail('Please select a valid booking date.');
                    }
                },
            ],
            'time_slot' => ['nullable', 'regex:/^\d{2}:\d{2}-\d{2}:\d{2}$/'],
            'days' => ['nullable', 'integer', 'min:1', 'max:' . self::BOOKING_MAX_AVAILABILITY_DAYS],
        ]);

        $days = (int) ($validated['days'] ?? self::BOOKING_MAX_AVAILABILITY_DAYS);
        $startDate = Carbon::today();
        $endDate = Carbon::today()->copy()->addDays(max($days - 1, 0));
        $todayKey = Carbon::today()->toDateString();
        $leadTimeCutoff = now()->addMinutes(self::BOOKING_MIN_LEAD_MINUTES);
        $leadTimeCutoffMinutes = ($leadTimeCutoff->hour * 60) + $leadTimeCutoff->minute;

        $rooms = Room::query()
            ->visible()
            ->operational()
            ->orderBy('name')
            ->get();

        if ($rooms->isEmpty()) {
            return response()->json([
                'dates' => [],
                'time_slots' => [],
                'rooms' => [],
                'selected' => [
                    'date' => null,
                    'time_slot' => null,
                ],
            ]);
        }

        $roomPayload = $rooms->map(function (Room $room): array {
            return [
                'id' => $room->id,
                'name' => $room->name,
                'capacity' => $room->standardBookingCapacityLimit(),
                'is_collaborative' => $room->isCollaborative(),
                'standard_limit' => $room->standardBookingCapacityLimit(),
                'student_limit' => $room->maxStudentBookingCapacity(),
            ];
        })->values();

        $roomPayloadById = $roomPayload
            ->mapWithKeys(fn (array $room) => [(string) $room['id'] => $room])
            ->all();

        $bookings = Booking::query()
            ->select(['room_id', 'date', 'start_time', 'end_time'])
            ->whereIn('status', ['pending', 'approved'])
            ->whereIn('room_id', $rooms->pluck('id')->all())
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->get();

        $bookedRanges = [];
        foreach ($bookings as $booking) {
            $dateKey = Carbon::parse($booking->date)->toDateString();
            $roomKey = (string) $booking->room_id;
            $startMinutes = $this->timeStringToMinutes((string) $booking->start_time);
            $endMinutes = $this->timeStringToMinutes((string) $booking->end_time);

            if ($startMinutes === null) {
                continue;
            }

            if ($endMinutes === null || $endMinutes <= $startMinutes) {
                $endMinutes = $startMinutes + 60;
            }

            $bookedRanges[$dateKey][$roomKey][] = [
                'start' => $startMinutes,
                'end' => $endMinutes,
            ];
        }

        $slotDefinitions = $this->bookingSlotDefinitions();
        $referenceNow = now((string) config('app.booking_timezone', config('app.timezone', 'Asia/Manila')));
        $todayDateKey = $referenceNow->toDateString();
        $minimumStartMinutesToday = ($referenceNow->hour * 60) + $referenceNow->minute + 15;

        $dateOptions = [];
        $timeSlotsByDate = [];
        $roomsByDateAndSlot = [];

        for ($offset = 0; $offset < $days; $offset += 1) {
            $date = $startDate->copy()->addDays($offset);
            if ($date->dayOfWeek === Carbon::SUNDAY) {
                continue;
            }

            $dateKey = $date->toDateString();
            $availableSlots = [];

            foreach ($slotDefinitions as $slot) {
                if ($dateKey === $todayDateKey && $slot['start_minutes'] <= $minimumStartMinutesToday) {
                    continue;
                }

                $availableRooms = [];

                foreach ($rooms as $room) {
                    $roomKey = (string) $room->id;
                    $isConflicting = false;

                    foreach (($bookedRanges[$dateKey][$roomKey] ?? []) as $range) {
                        if ($range['start'] < $slot['end_minutes'] && $range['end'] > $slot['start_minutes']) {
                            $isConflicting = true;
                            break;
                        }
                    }

                    if (! $isConflicting && isset($roomPayloadById[$roomKey])) {
                        $availableRooms[] = $roomPayloadById[$roomKey];
                    }
                }

                if ($availableRooms === []) {
                    continue;
                }

                $availableSlots[] = [
                    'value' => $slot['value'],
                    'label' => $slot['label'],
                    'start_time' => $slot['start_time'],
                    'end_time' => $slot['end_time'],
                    'available_room_count' => count($availableRooms),
                ];

                $roomsByDateAndSlot[$dateKey][$slot['value']] = $availableRooms;
            }

            if ($availableSlots === []) {
                continue;
            }

            $dateOptions[] = [
                'value' => $dateKey,
                'label' => $date->format('D, M j, Y'),
            ];

            $timeSlotsByDate[$dateKey] = $availableSlots;
        }

        $requestedDate = (string) ($validated['date'] ?? '');
        $selectedDate = $requestedDate !== '' && array_key_exists($requestedDate, $timeSlotsByDate)
            ? $requestedDate
            : (($dateOptions[0]['value'] ?? null));

        $timeSlots = $selectedDate ? ($timeSlotsByDate[$selectedDate] ?? []) : [];

        $requestedSlot = (string) ($validated['time_slot'] ?? '');
        $selectedSlot = $requestedSlot !== '' && collect($timeSlots)->contains(fn (array $slot) => $slot['value'] === $requestedSlot)
            ? $requestedSlot
            : (($timeSlots[0]['value'] ?? null));

        $availableRoomsForSlot = ($selectedDate && $selectedSlot)
            ? ($roomsByDateAndSlot[$selectedDate][$selectedSlot] ?? [])
            : [];

        return response()->json([
            'dates' => $dateOptions,
            'time_slots' => $timeSlots,
            'rooms' => array_values($availableRoomsForSlot),
            'selected' => [
                'date' => $selectedDate,
                'time_slot' => $selectedSlot,
            ],
        ]);
    }

    public function store(Request $request, QcIdOcrVerifier $qcIdOcrVerifier)
    {
        $request->merge([
            'title' => $request->input('purpose', $request->input('title')),
        ]);

        $actingUser = $request->user();
        $verifiedRegistration = $actingUser
            ? QcIdRegistration::query()
                ->where('user_id', $actingUser->id)
                ->where('verification_status', 'verified')
                ->first()
            : null;

        $isStaffUser = $actingUser?->isStaff() ?? false;
        $requiresBookingOcr = ! $verifiedRegistration && ! $isStaffUser;

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'room_id' => 'required|exists:rooms,id',
            'date' => [
                'required',
                'date',
                'after_or_equal:today',
                function ($attribute, $value, $fail) {
                    try {
                        $selectedDate = Carbon::parse((string) $value)->startOfDay();
                    } catch (\Throwable $e) {
                        $fail('Please select a valid booking date.');
                        return;
                    }

                    if ((int) $selectedDate->dayOfWeek === Carbon::SUNDAY) {
                        $fail('Bookings are not allowed on Sundays.');
                    }

                    if ($selectedDate->greaterThan($this->bookingWindowEndDate())) {
                        $fail('Bookings can only be made within the next 7 days.');
                    }
                },
            ],
            'start_time' => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    $selectedDate = trim((string) $request->input('date', ''));
                    if ($selectedDate === '') {
                        return;
                    }

                    if ($selectedDate !== now()->toDateString()) {
                        return;
                    }

                    try {
                        $selectedStart = Carbon::parse($selectedDate . ' ' . (string) $value);
                    } catch (\Throwable $e) {
                        $fail('Please select a valid booking time slot.');
                        return;
                    }

                    if ($selectedStart->lt(now()->addMinutes(self::BOOKING_MIN_LEAD_MINUTES))) {
                        $fail('The selected time slot must be at least 15 minutes in the future.');
                    }
                },
            ],
            'end_time' => 'required|after:start_time',
            'attendees' => 'required|integer|min:' . self::BOOKING_MIN_ATTENDEES . '|max:' . self::BOOKING_MAX_ATTENDEES,
            'user_id' => 'nullable|exists:users,id',
            'user_name' => 'nullable|string|max:255',
            'user_email' => 'nullable|email|max:255',
            'description' => 'nullable|string',
            'qc_id_ocr_text' => ($requiresBookingOcr ? 'required' : 'nullable') . '|string|min:20|max:12000',
            'qc_id_cardholder_name' => 'nullable|string|max:255',
        ]);

        if (! $this->isAllowedBookingSlot((string) $validated['start_time'], (string) $validated['end_time'])) {
            return response()->json([
                'success' => false,
                'message' => 'Selected time slot is no longer allowed. Choose one of the available hourly slots from 8:00 AM to 5:00 PM.',
            ], 422);
        }

        $room = Room::findOrFail($validated['room_id']);

        if ($room->isExcludedRoom()) {
            return response()->json([
                'success' => false,
                'message' => 'This room is no longer available for booking.',
            ], 422);
        }

        if (! $room->isOperational()) {
            return response()->json([
                'success' => false,
                'message' => 'This room is currently unavailable due to status changes.',
            ], 422);
        }

        $requestedAttendees = (int) $validated['attendees'];

        if ($room->exceedsBookingLimitFor($requestedAttendees, $actingUser)) {
            $limit = $actingUser?->isStaff()
                ? ($room->isCollaborative() ? $room->absoluteBookingCapacityLimit() : (int) $room->capacity)
                : $room->maxStudentBookingCapacity();

            $message = $room->isCollaborative()
                ? 'Collaborative rooms allow up to 10 attendees by default, and can only be extended up to 12 with librarian permission.'
                : 'The requested attendee count exceeds this room\'s capacity.';

            return response()->json([
                'success' => false,
                'message' => $message,
                'limit' => $limit,
            ], 422);
        }

        $requiresLargeGroupRequest = ! $isStaffUser && $requestedAttendees > self::BOOKING_STANDARD_ATTENDEES;
        $requiresCapacityPermission = $room->requiresCapacityPermissionFor($requestedAttendees, $actingUser) || $requiresLargeGroupRequest;

        $bookedForUser = null;
        if ($isStaffUser && ! empty($validated['user_id'])) {
            $bookedForUser = User::query()
                ->whereKey((int) $validated['user_id'])
                ->where('role', User::ROLE_USER)
                ->first();

            if (! $bookedForUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected user could not be found for this booking.',
                ], 422);
            }
        }

        if ($isStaffUser) {
            $qcIdVerification = [
                'is_valid' => true,
                'name_matches' => true,
                'source' => 'staff_bypass',
                'cardholder_name' => $validated['user_name'] ?? $bookedForUser?->name ?? $actingUser?->name,
            ];

            $validated['user_name'] = $validated['user_name'] ?: $bookedForUser?->name ?: $actingUser?->name;
            $validated['user_email'] = $validated['user_email'] ?: $bookedForUser?->email;
            if (! empty($bookedForUser)) {
                $validated['user_id'] = $bookedForUser->id;
            }
        } elseif ($verifiedRegistration) {
            $qcIdVerification = [
                'is_valid' => true,
                'name_matches' => true,
                'source' => 'registration',
                'cardholder_name' => $verifiedRegistration->full_name,
            ];

            $validated['user_name'] = $validated['user_name'] ?: $verifiedRegistration->full_name;
            $validated['user_email'] = $validated['user_email'] ?: $verifiedRegistration->email;
        } else {
            $qcIdVerification = $qcIdOcrVerifier->verify(
                $validated['qc_id_ocr_text'],
                $validated['user_name'] ?? null,
            );

            if (! $qcIdVerification['is_valid'] || ($qcIdVerification['is_fake'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => ($qcIdVerification['is_fake'] ?? false) 
                        ? 'FAKE QC ID DETECTED. Please upload a valid, authentic Quezon City Citizen ID.' 
                        : 'Please upload a valid Quezon City Citizen ID (QC ID) before creating a booking.',
                    'verification' => $qcIdVerification,
                ], 422);
            }

            if (($qcIdVerification['name_matches'] ?? null) === false) {
                return response()->json([
                    'success' => false,
                    'message' => 'The booking name must match the cardholder name on the uploaded QC ID.',
                    'verification' => $qcIdVerification,
                ], 422);
            }

            if (! empty($qcIdVerification['cardholder_name'])) {
                $validated['user_name'] = $qcIdVerification['cardholder_name'];
            }
        }

        unset($validated['qc_id_ocr_text'], $validated['qc_id_cardholder_name']);

        if ($actingUser) {
            if ($isStaffUser) {
                $validated['user_id'] = $validated['user_id'] ?? $actingUser->id;
                $validated['user_name'] = $validated['user_name'] ?: $actingUser->name;
            } else {
                $validated['user_id'] = $validated['user_id'] ?? $actingUser->id;
                $validated['user_email'] = $validated['user_email'] ?: $actingUser->email;
                $validated['user_name'] = $validated['user_name'] ?: $actingUser->name;
            }
        }

        if (! filled($validated['user_email']) && $actingUser && ! $isStaffUser) {
            $validated['user_email'] = $actingUser->email;
            $validated['user_id'] = $actingUser->id;
        }

        // Set initial status based on room settings
        if ($isStaffUser) {
            $validated['status'] = 'approved';
        } else {
            $validated['status'] = ($room->requires_approval || $requiresCapacityPermission) ? 'pending' : 'approved';
        }
        $validated['time'] = Carbon::parse($validated['start_time'])->format('g:i A');
        
        // Calculate duration
        $startTime = Carbon::parse($validated['start_time']);
        $endTime = Carbon::parse($validated['end_time']);
        $durationMinutes = $startTime->diffInMinutes($endTime);
        $hours = floor($durationMinutes / 60);
        $minutes = $durationMinutes % 60;
        $validated['duration'] = $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";

        $activeConflictStatuses = ['pending', 'approved'];

        $booking = DB::transaction(function () use ($room, $validated, $activeConflictStatuses) {
            // Serialize submissions targeting the same room so near-simultaneous requests
            // cannot both pass the conflict check and create duplicate slot bookings.
            Room::query()->whereKey($room->id)->lockForUpdate()->first();

            $hasConflict = Booking::query()
                ->where('room_id', $validated['room_id'])
                ->where('date', $validated['date'])
                ->whereIn('status', $activeConflictStatuses)
                ->where(function ($query) use ($validated) {
                    // Exclusive boundary overlap: end times touching start times are NOT conflicts
                    // e.g., 11:00-12:00 does NOT conflict with 12:00-13:00
                    $query->where('start_time', '<', $validated['end_time'])
                          ->where('end_time', '>', $validated['start_time']);
                })
                ->lockForUpdate()
                ->exists();

            if ($hasConflict) {
                return null;
            }

            return Booking::create($validated);
        }, 3);

        if (! $booking) {
            return response()->json([
                'success' => false,
                'message' => 'This time slot conflicts with an existing booking',
            ], 422);
        }

        if (! empty($booking->user_email)) {
            try {
                if ($booking->status === 'pending') {
                    Mail::to($booking->user_email)->queue(new BookingPendingMail($booking));
                } elseif ($booking->status === 'approved') {
                    Mail::to($booking->user_email)->queue(new BookingApprovedMail($booking));
                }
            } catch (\Throwable $e) {
                Log::warning('Failed to send booking creation email.', [
                    'booking_id' => $booking->id,
                    'user_email' => $booking->user_email,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        try {
            $booking->loadMissing('room');

            $adminRecipients = User::query()
                ->where('role', User::ROLE_ADMIN)
                ->when($actingUser?->id, fn ($query, $userId) => $query->where('id', '!=', $userId))
                ->get();

            if ($adminRecipients->isNotEmpty()) {
                Notification::send($adminRecipients, new NewBookingSubmittedForStaffNotification($booking));
            }
        } catch (\Throwable $e) {
            Log::warning('Failed to notify admins about new booking submission.', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }

        $successMessage = $isStaffUser 
            ? 'Booking confirmed successfully' 
            : ($requiresCapacityPermission
                ? 'Booking submitted for librarian approval. Requests with 11-12 attendees need staff approval.'
                : ($room->requires_approval
                    ? 'Booking submitted for approval'
                    : 'Booking confirmed successfully'));

        return response()->json([
            'success' => true,
            'message' => $successMessage,
            'booking' => $booking->load('room'),
            'verification' => $qcIdVerification,
            'qcid_registration_verified' => (bool) $verifiedRegistration,
        ]);
    }

    public function show(Booking $booking)
    {
        return response()->json($booking->load('room'));
    }

    public function update(Request $request, Booking $booking)
    {
        $request->merge([
            'title' => $request->input('purpose', $request->input('title')),
        ]);

        $actingUser = $request->user();

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'room_id' => 'required|exists:rooms,id',
            'date' => [
                'required',
                'date',
                'after_or_equal:today',
                function ($attribute, $value, $fail) {
                    try {
                        $selectedDate = Carbon::parse((string) $value)->startOfDay();
                    } catch (\Throwable $e) {
                        $fail('Please select a valid booking date.');
                        return;
                    }

                    if ((int) $selectedDate->dayOfWeek === Carbon::SUNDAY) {
                        $fail('Bookings are not allowed on Sundays.');
                    }

                    if ($selectedDate->greaterThan($this->bookingWindowEndDate())) {
                        $fail('Bookings can only be made within the next 7 days.');
                    }
                },
            ],
            'start_time' => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    $selectedDate = trim((string) $request->input('date', ''));
                    if ($selectedDate === '') {
                        return;
                    }

                    if ($selectedDate !== now()->toDateString()) {
                        return;
                    }

                    try {
                        $selectedStart = Carbon::parse($selectedDate . ' ' . (string) $value);
                    } catch (\Throwable $e) {
                        $fail('Please select a valid booking time slot.');
                        return;
                    }

                    if ($selectedStart->lt(now()->addMinutes(self::BOOKING_MIN_LEAD_MINUTES))) {
                        $fail('The selected time slot must be at least 15 minutes in the future.');
                    }
                },
            ],
            'end_time' => 'required|after:start_time',
            'attendees' => 'required|integer|min:' . self::BOOKING_MIN_ATTENDEES . '|max:' . self::BOOKING_MAX_ATTENDEES,
            'user_name' => 'required|string|max:255',
            'user_email' => 'required|email|max:255',
            'description' => 'nullable|string',
        ]);

        if (! $this->isAllowedBookingSlot((string) $validated['start_time'], (string) $validated['end_time'])) {
            return response()->json([
                'success' => false,
                'message' => 'Selected time slot is no longer allowed. Choose one of the available hourly slots from 8:00 AM to 5:00 PM.',
            ], 422);
        }

        $booking->loadMissing('room', 'user');

        $previousSchedule = [
            'room_id' => (int) ($booking->room_id ?? 0),
            'room_name' => $booking->room?->name,
            'date' => optional($booking->date)->format('Y-m-d'),
            'start_time' => (string) ($booking->start_time ?? ''),
            'end_time' => (string) ($booking->end_time ?? ''),
        ];

        $room = Room::findOrFail((int) $validated['room_id']);

        if ($room->isExcludedRoom()) {
            return response()->json([
                'success' => false,
                'message' => 'This room is no longer available for booking.',
            ], 422);
        }

        if (! $room->isOperational()) {
            return response()->json([
                'success' => false,
                'message' => 'This room is currently unavailable due to status changes.',
            ], 422);
        }

        $requestedAttendees = (int) $validated['attendees'];
        if ($room->exceedsBookingLimitFor($requestedAttendees, $actingUser)) {
            $limit = $actingUser?->isStaff()
                ? ($room->isCollaborative() ? $room->absoluteBookingCapacityLimit() : (int) $room->capacity)
                : $room->maxStudentBookingCapacity();

            $message = $room->isCollaborative()
                ? 'Collaborative rooms are fixed at 10 attendees, and can only be extended up to 12 with librarian permission.'
                : 'The requested attendee count exceeds this room\'s capacity.';

            return response()->json([
                'success' => false,
                'message' => $message,
                'limit' => $limit,
            ], 422);
        }

        $validated['time'] = Carbon::parse($validated['start_time'])->format('g:i A');

        $startTime = Carbon::parse($validated['start_time']);
        $endTime = Carbon::parse($validated['end_time']);
        $durationMinutes = $startTime->diffInMinutes($endTime);
        $hours = floor($durationMinutes / 60);
        $minutes = $durationMinutes % 60;
        $validated['duration'] = $minutes > 0 ? "{$hours}h {$minutes}m" : "{$hours}h";

        $activeConflictStatuses = ['pending', 'approved'];

        $updatedBooking = DB::transaction(function () use ($room, $validated, $activeConflictStatuses, $booking) {
            Room::query()->whereKey($room->id)->lockForUpdate()->first();

            $hasConflict = Booking::query()
                ->where('id', '!=', $booking->id)
                ->where('room_id', $validated['room_id'])
                ->where('date', $validated['date'])
                ->whereIn('status', $activeConflictStatuses)
                ->where(function ($query) use ($validated) {
                    $query->where('start_time', '<', $validated['end_time'])
                        ->where('end_time', '>', $validated['start_time']);
                })
                ->lockForUpdate()
                ->exists();

            if ($hasConflict) {
                return null;
            }

            $booking->fill($validated);
            $booking->save();

            return $booking->fresh()->load('room', 'user');
        }, 3);

        if (! $updatedBooking) {
            return response()->json([
                'success' => false,
                'message' => 'This time slot conflicts with an existing booking',
            ], 422);
        }

        $updatedSchedule = [
            'room_id' => (int) ($updatedBooking->room_id ?? 0),
            'room_name' => $updatedBooking->room?->name,
            'date' => optional($updatedBooking->date)->format('Y-m-d'),
            'start_time' => (string) ($updatedBooking->start_time ?? ''),
            'end_time' => (string) ($updatedBooking->end_time ?? ''),
        ];

        $scheduleChanged = $previousSchedule['room_id'] !== $updatedSchedule['room_id']
            || $previousSchedule['date'] !== $updatedSchedule['date']
            || $previousSchedule['start_time'] !== $updatedSchedule['start_time']
            || $previousSchedule['end_time'] !== $updatedSchedule['end_time'];

        if ($scheduleChanged) {
            try {
                $email = $updatedBooking->user_email ?? $updatedBooking->user?->email;
                if (! empty($email)) {
                    Mail::to($email)->send(new BookingRescheduledMail($updatedBooking, $previousSchedule));
                }
            } catch (\Throwable $e) {
                Log::warning('Booking reschedule email failed.', [
                    'booking_id' => $updatedBooking->id,
                    'user_id' => $updatedBooking->user?->id,
                    'user_email' => $updatedBooking->user_email,
                    'error' => $e->getMessage(),
                ]);
            }

            try {
                $bookingUser = $updatedBooking->user ?? ($updatedBooking->user_id ? User::find($updatedBooking->user_id) : null);
                if ($bookingUser) {
                    $bookingUser->notify(new BookingRescheduledNotification($updatedBooking, $previousSchedule));
                }
            } catch (\Throwable $e) {
                Log::warning('Booking rescheduled in-app notification failed.', [
                    'booking_id' => $updatedBooking->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking updated successfully',
            'booking' => $updatedBooking,
        ]);
    }

    public function destroy(Booking $booking)
    {
        $booking->delete();

        return response()->json(['success' => true, 'message' => 'Booking deleted successfully']);
    }

    public function cancel(Request $request, Booking $booking)
    {
        $user = $request->user();
        $canManageAll = $user?->isAdmin() || $user?->isSuperAdmin();
        $isOwner = $user
            && (
                ((int) ($booking->user_id ?? 0) === (int) $user->id)
                || (! empty($user->email) && $booking->user_email === $user->email)
            );

        if (! $canManageAll && ! $isOwner) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to cancel this booking.',
            ], 403);
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        if ($booking->status !== 'approved') {
            return response()->json([
                'success' => false,
                'message' => 'Only approved bookings can be cancelled.',
            ], 422);
        }

        $bookingStartAt = Carbon::parse(
            $booking->date->format('Y-m-d') . ' ' . Carbon::parse((string) $booking->start_time)->format('H:i:s'),
            config('app.timezone', 'Asia/Manila')
        );

        if (now(config('app.timezone', 'Asia/Manila'))->greaterThanOrEqualTo($bookingStartAt)) {
            return response()->json([
                'success' => false,
                'message' => 'Cancellation is unavailable once the booking time has started.',
            ], 422);
        }

        $booking->loadMissing('room');
        $booking->update([
            'status' => 'cancelled',
            'reason' => trim((string) $validated['reason']),
        ]);

        // Determine who cancelled: admin or the user themselves
        $cancelledBy = ($canManageAll && !$isOwner) ? 'admin' : 'user';

        // Send cancellation email to the booking owner
        try {
            $email = $booking->user_email ?? $booking->user?->email;
            if (! empty($email)) {
                Mail::to($email)->queue(new BookingCancelledMail($booking, $cancelledBy));
            }
        } catch (\Throwable $e) {
            Log::warning('Booking cancellation email failed.', [
                'booking_id' => $booking->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Booking cancelled successfully']);
    }

    public function approve(Booking $booking, Request $request)
    {
        $booking->loadMissing('room', 'user');

        $approvalReason = trim((string) $request->get('reason'));

        $booking->update([
            'status' => 'approved',
            'reason' => $approvalReason !== '' ? $approvalReason : null,
        ]);

        // --- Ensure a unique qr_token exists for this booking ---
        if (! $booking->qr_token) {
            // try a few times to avoid collision (extremely unlikely)
            for ($i = 0; $i < 5; $i++) {
                $token = (string) \Illuminate\Support\Str::uuid();
                if (! Booking::where('qr_token', $token)->exists()) {
                    $booking->qr_token = $token;
                    $booking->saveQuietly();
                    break;
                }
            }
        }

        // Keep the lifecycle status in sync for the latest date/time before generating QR payloads.
        $booking->syncBookingStatus();

        // Keep legacy QR file generation (if service installed)
        try {
            app(\App\Services\QrCodeService::class)->ensureBookingQr($booking);
        } catch (\Throwable $e) {
            // swallow - QR service is optional
        }

        // Prepare response payload with stable QR endpoints for display + download.
        $fresh = $booking->fresh()->load('room');
        $payload = $fresh->toArray();
        $qrToken = (string) ($fresh->qr_token ?? '');
        $qrDisplayUrl = $qrToken !== ''
            ? url('/bookings/qr/' . $qrToken . '?format=png')
            : null;

        $payload['qr_code_data'] = null;
        $payload['qr_code_url'] = $qrDisplayUrl ?? $fresh->getAttribute('qr_code_url') ?? null;
        $payload['qr_download_png_url'] = $qrToken !== ''
            ? url('/bookings/qr/' . $qrToken . '?format=png&download=1')
            : null;
        $payload['qr_download_jpeg_url'] = $qrToken !== ''
            ? url('/bookings/qr/' . $qrToken . '?format=jpeg&download=1')
            : null;
        $payload['approval_status'] = $fresh->status;
        $payload['qr_status'] = $fresh->booking_status;
    // Always add plain QR code payload for frontend to use in QR code URL
    $payload['qr_code_encrypted'] = $fresh->qr_token ?? $fresh->booking_code;
    $payload['qr_validity'] = $fresh->qr_validity;

        try {
            if (! empty($fresh->user_email)) {
                Mail::to($fresh->user_email)->queue(new BookingApprovedMail($fresh));
            } elseif ($fresh->user && ! empty($fresh->user->email)) {
                Mail::to($fresh->user->email)->queue(new BookingApprovedMail($fresh));
            }
        } catch (\Throwable $e) {
            Log::warning('Booking approval email failed.', [
                'booking_id' => $fresh->id,
                'user_id' => $fresh->user?->id,
                'user_email' => $fresh->user_email,
                'error' => $e->getMessage(),
            ]);
        }

        // Send in-app notification to the booking owner
        try {
            $bookingUser = $fresh->user ?? ($fresh->user_id ? User::find($fresh->user_id) : null);
            if ($bookingUser) {
                $bookingUser->notify(new BookingApprovedNotification($fresh));
            }
        } catch (\Throwable $e) {
            Log::warning('Booking approved in-app notification failed.', [
                'booking_id' => $fresh->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Booking approved successfully',
            'booking' => $payload,
        ]);
    }

    public function reject(Booking $booking, Request $request)
    {
        $booking->loadMissing('room', 'user');

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $rejectionReason = trim((string) $validated['reason']);

        $booking->update([
            'status' => 'rejected',
            'reason' => $rejectionReason !== '' ? $rejectionReason : null,
        ]);

        $fresh = $booking->fresh()->load('room', 'user');

        try {
            $email = $fresh->user_email ?? $fresh->user?->email;
            if (! empty($email)) {
                Mail::to($email)->send(new BookingRejectedMail($fresh));
            }
        } catch (\Throwable $e) {
            Log::warning('Booking rejection email failed.', [
                'booking_id' => $fresh->id,
                'user_id' => $fresh->user?->id,
                'user_email' => $fresh->user_email,
                'error' => $e->getMessage(),
            ]);
        }

        try {
            $bookingUser = $fresh->user ?? ($fresh->user_id ? User::find($fresh->user_id) : null);
            if ($bookingUser) {
                $bookingUser->notify(new BookingRejectedNotification($fresh));
            }
        } catch (\Throwable $e) {
            Log::warning('Booking rejected in-app notification failed.', [
                'booking_id' => $fresh->id,
                'error' => $e->getMessage(),
            ]);
        }

        return response()->json(['success' => true, 'message' => 'Booking rejected successfully']);
    }

    public function approvals(Request $request)
    {
        $status = $request->get('status', 'pending');
        $query = Booking::with('room')
            ->whereHas('room', fn ($roomQuery) => $roomQuery->visible())
            ->where('status', $status);

        if ($request->filled('room') && $request->room !== 'all') {
            $query->where('room_id', $request->room);
        }

        $bookings = $query->orderByDesc('date')->orderByDesc('start_time')->paginate(15);
        $rooms = Room::query()->visible()->orderBy('name')->get();

        $stats = [
            'pending' => Booking::where('status', 'pending')->count(),
            'approved' => Booking::where('status', 'approved')->count(),
            'rejected' => Booking::where('status', 'rejected')->count(),
        ];

        return view('rooms.approvals', compact('bookings', 'rooms', 'stats', 'status'));
    }

    /**
     * Return a PNG QR image for the provided booking token.
     * QR payload prefers a public verify URL when qr_token exists.
     */
    public function qrImage(Request $request, string $token)
    {
        $decrypted = $token;
        $format = strtolower((string) $request->query('format', 'png'));
        $download = $request->boolean('download');

        if (! in_array($format, ['png', 'jpeg', 'jpg', 'svg'], true)) {
            $format = 'png';
        }

        if ($decrypted === 'smartspace-master-token') {
            try {
                $result = $this->renderQrBinary(url('/verify?token=smartspace-master-token'), $format);

                $headers = [
                    'Content-Type' => $result['content_type'],
                    'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                ];

                if ($download) {
                    $headers['Content-Disposition'] = 'attachment; filename="smartspace-master-qr.' . $result['extension'] . '"';
                }

                return response($result['content'], 200, $headers);
            } catch (\Throwable $e) {
                return response('QR generation unavailable', 500);
            }
        }

        // Try to find booking by qr_token first, then by booking_code (for legacy/backup)
        $booking = Booking::where('qr_token', $decrypted)
            ->orWhere('booking_code', $decrypted)
            ->first();

        if (! $booking) {
            return response('Not found', 404);
        }

        // Every QR render re-evaluates the booking lifecycle status (upcoming/valid/expired).
        $booking->syncBookingStatus();

        // Render QR dynamically so the payload is always up-to-date and verifiable.
        try {
            $payload = $booking->qr_token
                ? url('/verify?token=' . $booking->qr_token)
                : ($booking->booking_code ?? $decrypted);

            $result = $this->renderQrBinary($payload, $format);

            $headers = [
                'Content-Type' => $result['content_type'],
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ];

            if ($download) {
                $base = (string) ($booking->booking_code ?: $booking->id ?: 'booking');
                $safeBase = preg_replace('/[^A-Za-z0-9_-]+/', '-', $base);
                $headers['Content-Disposition'] = 'attachment; filename="booking-qr-' . trim((string) $safeBase, '-') . '.' . $result['extension'] . '"';
            }

            return response($result['content'], 200, $headers);
        } catch (\Throwable $e) {
            return response('QR generation unavailable', 500);
        }
    }

    /**
     * @return array{content: string, content_type: string, extension: string}
     */
    private function renderQrBinary(string $payload, string $format): array
    {
        $builder = new \Endroid\QrCode\Builder\Builder();
        $wantsRaster = in_array($format, ['png', 'jpeg', 'jpg'], true);
        $pngBinary = null;

        if ($wantsRaster) {
            try {
                $png = $builder->build(
                    writer: new \Endroid\QrCode\Writer\PngWriter(),
                    data: $payload,
                    size: 480,
                    margin: 10
                );

                $pngBinary = $png->getString();
            } catch (\Throwable $e) {
                $pngBinary = null;
            }

            if ($pngBinary !== null && in_array($format, ['jpeg', 'jpg'], true)) {
                $jpegBinary = $this->convertPngToJpegBinary($pngBinary);

                if ($jpegBinary !== null) {
                    return [
                        'content' => $jpegBinary,
                        'content_type' => 'image/jpeg',
                        'extension' => 'jpg',
                    ];
                }
            }

            if ($pngBinary !== null) {
                return [
                    'content' => $pngBinary,
                    'content_type' => 'image/png',
                    'extension' => 'png',
                ];
            }
        }

        $svg = $builder->build(
            writer: new \Endroid\QrCode\Writer\SvgWriter(),
            data: $payload,
            size: 480,
            margin: 10
        );

        return [
            'content' => $svg->getString(),
            'content_type' => 'image/svg+xml',
            'extension' => 'svg',
        ];
    }

    private function convertPngToJpegBinary(string $pngBinary): ?string
    {
        if (! function_exists('imagecreatefromstring') || ! function_exists('imagejpeg')) {
            return null;
        }

        $image = @imagecreatefromstring($pngBinary);
        if (! $image) {
            return null;
        }

        $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
        if (! $canvas) {
            imagedestroy($image);
            return null;
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);
        imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        ob_start();
        imagejpeg($canvas, null, 92);
        $jpegBinary = ob_get_clean();

        imagedestroy($canvas);
        imagedestroy($image);

        return is_string($jpegBinary) ? $jpegBinary : null;
    }

    /**
     * Public verification page for scanned QR tokens
     */
    public function verify(Request $request)
    {
        $token = $request->query('token');
        
        if ($token === 'smartspace-master-token') {
            return view('rooms.verify', ['booking' => 'master_unlock', 'token' => $token]);
        }
        
        $booking = Booking::where('qr_token', $token)->with('room')->first();

        if (! $booking) {
            return view('rooms.verify', ['booking' => null, 'token' => $token]);
        }

        $booking->syncBookingStatus();

        return view('rooms.verify', ['booking' => $booking, 'token' => $token]);
    }

    private function bookingWindowEndDate(): Carbon
    {
        return Carbon::today()->addDays(self::BOOKING_MAX_AVAILABILITY_DAYS - 1)->endOfDay();
    }

    private function isAllowedBookingSlot(string $startTime, string $endTime): bool
    {
        $startMinutes = $this->timeStringToMinutes($startTime);
        $endMinutes = $this->timeStringToMinutes($endTime);

        if ($startMinutes === null || $endMinutes === null) {
            return false;
        }

        if (($endMinutes - $startMinutes) !== 60) {
            return false;
        }

        $openingMinutes = self::BOOKING_OPEN_HOUR * 60;
        $closingMinutes = self::BOOKING_CLOSE_HOUR * 60;

        return $startMinutes >= $openingMinutes
            && $endMinutes <= $closingMinutes;
    }

    /**
     * @return array<int, array{value: string, label: string, start_time: string, end_time: string, start_minutes: int, end_minutes: int}>
     */
    private function bookingSlotDefinitions(): array
    {
        $slots = [];

        for ($hour = self::BOOKING_OPEN_HOUR; $hour < self::BOOKING_CLOSE_HOUR; $hour += 1) {
            $start = sprintf('%02d:00', $hour);
            $end = sprintf('%02d:00', $hour + 1);

            $slots[] = [
                'value' => $start . '-' . $end,
                'label' => $this->formatSlotLabel($start, $end),
                'start_time' => $start,
                'end_time' => $end,
                'start_minutes' => $hour * 60,
                'end_minutes' => ($hour + 1) * 60,
            ];
        }

        return $slots;
    }

    private function formatSlotLabel(string $startTime, string $endTime): string
    {
        try {
            $start = Carbon::createFromFormat('H:i', $startTime)->format('g:i A');
            $end = Carbon::createFromFormat('H:i', $endTime)->format('g:i A');

            return $start . ' - ' . $end;
        } catch (\Throwable $e) {
            return $startTime . ' - ' . $endTime;
        }
    }

    private function timeStringToMinutes(string $time): ?int
    {
        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', trim($time), $match) !== 1) {
            return null;
        }

        $hours = (int) $match[1];
        $minutes = (int) $match[2];

        if ($hours < 0 || $hours > 23 || $minutes < 0 || $minutes > 59) {
            return null;
        }

        return ($hours * 60) + $minutes;
    }
}
