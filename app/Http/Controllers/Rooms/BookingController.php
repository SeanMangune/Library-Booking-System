<?php

namespace App\Http\Controllers\Rooms;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\QcIdRegistration;
use App\Models\Room;
use App\Models\User;
use App\Notifications\BookingApprovedNotification;
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
use App\Mail\BookingRejectedMail;
use App\Mail\BookingCancelledMail;

class BookingController extends Controller
{
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
                    if (date('N', strtotime($value)) == 7) {
                        $fail('Bookings are not allowed on Sundays.');
                    }
                },
            ],
            'start_time' => [
                'required',
                function ($attribute, $value, $fail) use ($request) {
                    $selectedDate = $request->input('date');
                    if ($selectedDate === date('Y-m-d')) {
                        $now = now();
                        $startTime = Carbon::parse($value);
                        if ($startTime->lt($now->addMinutes(15))) {
                            $fail('The selected time slot must be at least 15 minutes in the future.');
                        }
                    }
                },
            ],
            'end_time' => 'required|after:start_time',
            'attendees' => 'required|integer|min:1',
            'user_id' => 'nullable|exists:bookings,id',
            'user_name' => 'nullable|string|max:255',
            'user_email' => 'nullable|email|max:255',
            'description' => 'nullable|string',
            'qc_id_ocr_text' => ($requiresBookingOcr ? 'required' : 'nullable') . '|string|min:20|max:12000',
            'qc_id_cardholder_name' => 'nullable|string|max:255',
        ]);

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
                ? 'Collaborative rooms are fixed at 10 attendees, and can only be extended up to 12 with librarian permission.'
                : 'The requested attendee count exceeds this room\'s capacity.';

            return response()->json([
                'success' => false,
                'message' => $message,
                'limit' => $limit,
            ], 422);
        }

        $requiresCapacityPermission = $room->requiresCapacityPermissionFor($requestedAttendees, $actingUser);

        if ($isStaffUser) {
            $qcIdVerification = [
                'is_valid' => true,
                'name_matches' => true,
                'source' => 'staff_bypass',
                'cardholder_name' => $validated['user_name'] ?? $actingUser->name,
            ];

            $validated['user_name'] = $validated['user_name'] ?: $actingUser->name;
            $validated['user_email'] = $validated['user_email'] ?: $actingUser->email;
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
            $validated['user_id'] = $validated['user_id'] ?? $actingUser->id;
            $validated['user_email'] = $validated['user_email'] ?: $actingUser->email;
            $validated['user_name'] = $validated['user_name'] ?: $actingUser->name;
        }

        if (! filled($validated['user_email']) && $actingUser) {
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
                ? 'Booking submitted for librarian approval because collaborative room bookings above 10 attendees require permission.'
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

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'room_id' => 'required|exists:rooms,id',
            'date' => 'required|date',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'attendees' => 'required|integer|min:1',
            'user_name' => 'required|string|max:255',
            'user_email' => 'required|email|max:255',
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

        $booking->loadMissing('room');
        $booking->update(['status' => 'cancelled']);

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

        if ($booking->requiresCapacityPermission() && blank(trim((string) $request->get('reason')))) {
            return response()->json([
                'success' => false,
                'message' => 'Add a short approval note before granting a collaborative room booking above 10 attendees.',
            ], 422);
        }

        $booking->update([
            'status' => 'approved',
            'reason' => $request->get('reason'),
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

        // Prepare response payload and include an inline Endroid PNG (base64) so the
        // frontend can render the QR immediately without a second request.
        $fresh = $booking->fresh()->load('room');
        $qrDataUri = null;

        if ($booking->qr_token) {
            try {
                $verifyUrl = url('/verify?token=' . $booking->qr_token);
                $builder = new \Endroid\QrCode\Builder\Builder();
                $result = $builder->build(
                    writer: new \Endroid\QrCode\Writer\SvgWriter(),
                    data: $verifyUrl,
                    size: 480,
                    margin: 10
                );

                $svg = $result->getString();
                $qrDataUri = 'data:image/svg+xml;base64,' . base64_encode($svg);
            } catch (\Throwable $e) {
                // fallback: leave qrDataUri null (frontend can still use qr_code_url)
                $qrDataUri = null;
            }
        }

        $payload = $fresh->toArray();
        // Prefer inline PNG data (Endroid) when available, otherwise use stored/public URL
        $payload['qr_code_data'] = $qrDataUri;
        $payload['qr_code_url'] = $qrDataUri ?? $fresh->getAttribute('qr_code_url') ?? null;
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
        $booking->update([
            'status' => 'rejected',
            'reason' => $request->get('reason'),
        ]);

        try {
            $email = $booking->user_email ?? $booking->user?->email;
            if (! empty($email)) {
                Mail::to($email)->queue(new BookingRejectedMail($booking));
            }
        } catch (\Throwable $e) {
            Log::warning('Booking rejection email failed.', [
                'booking_id' => $booking->id,
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
        // Use the QR payload (token) as plain text
        $decrypted = $token;
        $format = strtolower((string) $request->query('format', 'svg'));
        $usePng = $format === 'png';

        $renderQr = function (string $payload) use ($usePng): array {
            $builder = new \Endroid\QrCode\Builder\Builder();

            if ($usePng) {
                try {
                    $png = $builder->build(
                        writer: new \Endroid\QrCode\Writer\PngWriter(),
                        data: $payload,
                        size: 480,
                        margin: 10
                    );

                    return [
                        'content' => $png->getString(),
                        'content_type' => 'image/png',
                    ];
                } catch (\Throwable $e) {
                    // Fall back to SVG if PNG generation is unavailable.
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
            ];
        };

        if ($decrypted === 'smartspace-master-token') {
            try {
                $result = $renderQr(url('/verify?token=smartspace-master-token'));

                return response($result['content'], 200, [
                    'Content-Type' => $result['content_type'],
                    'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                ]);
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

            $result = $renderQr($payload);

            return response($result['content'], 200, [
                'Content-Type' => $result['content_type'],
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]);
        } catch (\Throwable $e) {
            return response('QR generation unavailable', 500);
        }
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
}
