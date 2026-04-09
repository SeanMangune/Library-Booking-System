<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use App\Services\QrCodeService;
use Illuminate\Support\Facades\Schema;

class Booking extends Model
{
    private const DEFAULT_BOOKING_TIMEZONE = 'Asia/Manila';

    private static bool $bookingStatusColumnResolved = false;

    private static ?bool $bookingStatusColumnExists = null;

    protected $fillable = [
        'room_id',
        'title',
        'description',
        'user_id',
        'user_name',
        'user_email',
        'date',
        'time',
        'start_time',
        'end_time',
        'duration',
        'attendees',
        'status',
        'room_status',
        'booking_status',
        'qr_validity',
        'has_conflict',
        'conflicts_with',
        'reason',
        'qr_token',
        'booking_code',
    ];

    protected $casts = [
        'date' => 'date',
        'booking_status' => 'string',
        'room_status' => 'string',
        'has_conflict' => 'boolean',
    ];

    protected $appends = [
        'qr_code_url',
        'formatted_duration',
        'verify_url',
        'formatted_time',
        'formatted_date',
    ];

    protected static function booted(): void
    {
        static::saving(function (Booking $booking) {
            if (self::hasBookingStatusColumn()) {
                $booking->booking_status = $booking->determineBookingStatus();
            }
            $booking->qr_validity = $booking->determineQrValidity();
            $booking->room_status = $booking->determineRoomStatus();
        });

        static::retrieved(function (Booking $booking) {
            if (self::hasBookingStatusColumn()) {
                $booking->syncBookingStatus();
            }
            $booking->syncQrValidity();
        });

        static::created(function (Booking $booking) {
            // Only auto-generate QR code for bookings that are immediately approved
            // Pending bookings will get QR codes when approved via the approval process
            if ($booking->status === 'approved') {
                app(QrCodeService::class)->ensureBookingQr($booking);
            }
        });
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conflictingBooking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'conflicts_with');
    }

    public function exceedsCapacity(): bool
    {
        return $this->attendees > $this->room->capacity;
    }

    public function requiresCapacityPermission(): bool
    {
        $this->loadMissing('room', 'user');

        if (! $this->room) {
            return false;
        }

        return $this->room->requiresCapacityPermissionFor((int) $this->attendees, $this->user);
    }

    public function getFormattedTimeAttribute(): string
    {
        if ($this->start_time && $this->end_time) {
            return Carbon::parse($this->start_time)->format('g:i A') . ' - ' . Carbon::parse($this->end_time)->format('g:i A');
        }
        return $this->time ?? '';
    }

    public function getFormattedDateAttribute(): string
    {
        return $this->date ? $this->date->format('M d, Y') : '';
    }

    public function getQrCodeUrlAttribute(): ?string
    {
        // Primary: token-based dynamic QR (served by controller using Endroid)
        if ($this->qr_token) {
            return url('/bookings/qr/' . $this->qr_token);
        }

        // Only token-based dynamic QR images are supported in the schema
        return null;
    }

    /**
     * Human-friendly duration (derived from stored duration or from start/end times)
     */
    public function getFormattedDurationAttribute(): string
    {
        if ($this->duration) {
            return $this->duration;
        }

        if ($this->start_time && $this->end_time) {
            $start = Carbon::parse($this->start_time);
            $end = Carbon::parse($this->end_time);
            $minutes = $start->diffInMinutes($end);
            $hours = floor($minutes / 60);
            $mins = $minutes % 60;
            return $mins > 0 ? "{$hours}h {$mins}m" : "{$hours}h";
        }

        return '';
    }

    /**
     * Public verification URL for this booking (null when token missing).
     */
    public function getVerifyUrlAttribute(): ?string
    {
        if (! $this->qr_token) return null;
        return url('/verify?token=' . $this->qr_token);
    }

    public function determineBookingStatus(?Carbon $reference = null): string
    {
        $reference ??= now($this->bookingTimezone());

        $bookingDateValue = $this->normalizedBookingDateValue();
        if ($bookingDateValue === null) {
            return 'upcoming';
        }

        $currentDateValue = $reference->copy()->setTimezone($this->bookingTimezone())->format('Y-m-d');

        if ($bookingDateValue > $currentDateValue) {
            return 'upcoming';
        }

        if ($bookingDateValue < $currentDateValue) {
            return 'expired';
        }

        $currentTime = $reference->format('H:i:s');
        $startTime = $this->normalizeBookingTime($this->start_time);
        $endTime = $this->normalizeBookingTime($this->end_time);

        if ($startTime !== null && strcmp($currentTime, $startTime) < 0) {
            return 'upcoming';
        }

        if ($startTime !== null && $endTime !== null
            && strcmp($currentTime, $startTime) >= 0
            && strcmp($currentTime, $endTime) <= 0) {
            return 'valid';
        }

        if ($endTime !== null && strcmp($currentTime, $endTime) > 0) {
            return 'expired';
        }

        return 'upcoming';
    }

    public function determineRoomStatus(): string
    {
        if ($this->room_id && ! $this->relationLoaded('room')) {
            $this->load('room');
        }

        if ($this->room?->effectiveStatus() === 'maintenance') {
            return 'maintenance';
        }

        return $this->qr_validity === 'valid' ? 'occupied' : 'available';
    }

    public function syncBookingStatus(bool $persist = true): string
    {
        $calculatedStatus = $this->determineBookingStatus();

        if (! self::hasBookingStatusColumn()) {
            return $calculatedStatus;
        }

        if ($this->booking_status !== $calculatedStatus) {
            $this->forceFill(['booking_status' => $calculatedStatus]);

            if ($persist && $this->exists) {
                $this->saveQuietly();
            }
        }

        return $calculatedStatus;
    }

    /**
     * Determine QR validity based on approval status + current date/time window.
     *
     * Returns 'valid' ONLY when:
     *  - status is 'approved'
     *  - booking date is TODAY
     *  - current time is within start_time – end_time
     *
     * Returns 'not_valid' for everything else (pending, cancelled, future/past dates,
     * or outside the scheduled time window).
     */
    public function determineQrValidity(?\Carbon\Carbon $reference = null): string
    {
        $reference ??= now($this->bookingTimezone());

        // Non-approved bookings are never valid
        if ($this->status !== 'approved') {
            return 'not_valid';
        }

        $bookingDateValue = $this->normalizedBookingDateValue();
        if ($bookingDateValue === null || blank($this->start_time) || blank($this->end_time)) {
            return 'not_valid';
        }

        $currentDateValue = $reference->copy()->setTimezone($this->bookingTimezone())->format('Y-m-d');

        // Must be today — future or past dates are not valid
        if ($bookingDateValue !== $currentDateValue) {
            return 'not_valid';
        }

        // Check time window
        $currentTime = $reference->format('H:i:s');
        $startTime   = $this->normalizeBookingTime($this->start_time);
        $endTime     = $this->normalizeBookingTime($this->end_time);

        if ($startTime !== null && $endTime !== null
            && strcmp($currentTime, $startTime) >= 0
            && strcmp($currentTime, $endTime) <= 0) {
            return 'valid';
        }

        return 'not_valid';
    }

    private static function hasBookingStatusColumn(): bool
    {
        if (self::$bookingStatusColumnResolved) {
            return (bool) self::$bookingStatusColumnExists;
        }

        self::$bookingStatusColumnResolved = true;

        try {
            self::$bookingStatusColumnExists = Schema::hasColumn('bookings', 'booking_status');
        } catch (\Throwable $exception) {
            self::$bookingStatusColumnExists = false;
        }


        return (bool) self::$bookingStatusColumnExists;
    }

    /**
     * Re-evaluate and persist qr_validity if it has drifted.
     */
    public function syncQrValidity(bool $persist = true): string
    {
        $calculated = $this->determineQrValidity();

        if ($this->qr_validity !== $calculated) {
            $this->forceFill(['qr_validity' => $calculated]);

            if ($persist && $this->exists) {
                $this->saveQuietly();
            }
        }

        return $calculated;
    }
    private function normalizeBookingTime($time): ?string
    {
        if (blank($time)) {
            return null;
        }

        try {
            return Carbon::parse((string) $time)->format('H:i:s');
        } catch (\Throwable $exception) {
            return null;
        }
    }

    private function bookingTimezone(): string
    {
        return (string) config('app.booking_timezone', self::DEFAULT_BOOKING_TIMEZONE);
    }

    private function normalizedBookingDateValue(): ?string
    {
        $rawDate = $this->getRawOriginal('date');
        if (is_string($rawDate) && preg_match('/^\d{4}-\d{2}-\d{2}/', $rawDate, $matches) === 1) {
            return $matches[0];
        }

        if ($this->date instanceof Carbon) {
            return $this->date->format('Y-m-d');
        }

        if (blank($this->date)) {
            return null;
        }

        try {
            return Carbon::parse((string) $this->date, $this->bookingTimezone())->format('Y-m-d');
        } catch (\Throwable $exception) {
            return null;
        }
    }

    public function scopeToday($query)
    {
        return $query->whereDate('date', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->whereDate('date', '>', today());
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopePendingActive($query, ?Carbon $reference = null)
    {
        $reference ??= now(config('app.booking_timezone', self::DEFAULT_BOOKING_TIMEZONE));
        $today = $reference->toDateString();
        $currentTime = $reference->format('H:i:s');

        return $query
            ->where('status', 'pending')
            ->where(function ($pendingQuery) use ($today, $currentTime) {
                $pendingQuery
                    ->whereDate('date', '>', $today)
                    ->orWhere(function ($todayQuery) use ($today, $currentTime) {
                        $todayQuery
                            ->whereDate('date', '=', $today)
                            ->where(function ($timeQuery) use ($currentTime) {
                                $timeQuery
                                    ->whereNull('end_time')
                                    ->orWhereTime('end_time', '>=', $currentTime);
                            });
                    });
            });
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeByStatus($query, $status)
    {
        if ($status && $status !== 'all') {
            return $query->where('status', $status);
        }
        return $query;
    }

    public function scopeByRoom($query, $roomId)
    {
        if ($roomId && $roomId !== 'all') {
            return $query->where('room_id', $roomId);
        }
        return $query;
    }

    public function scopeByTimePeriod($query, $period)
    {
        return match($period) {
            'today' => $query->whereDate('date', today()),
            'this_week' => $query->whereBetween('date', [now()->startOfWeek(), now()->endOfWeek()]),
            'this_month' => $query->whereMonth('date', now()->month)->whereYear('date', now()->year),
            'past' => $query->whereDate('date', '<', today()),
            default => $query,
        };
    }

    public function hasTimeConflict(): bool
    {
        return Booking::where('room_id', $this->room_id)
            ->where('id', '!=', $this->id)
            ->where('date', $this->date)
            ->where('status', 'approved')
            ->where(function($query) {
                $query->whereBetween('start_time', [$this->start_time, $this->end_time])
                    ->orWhereBetween('end_time', [$this->start_time, $this->end_time])
                    ->orWhere(function($q) {
                        $q->where('start_time', '<=', $this->start_time)
                          ->where('end_time', '>=', $this->end_time);
                    });
            })
            ->exists();
    }
}
