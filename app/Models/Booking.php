<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use App\Services\QrCodeService;

class Booking extends Model
{
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
            $booking->booking_status = $booking->determineBookingStatus();
            $booking->qr_validity    = $booking->determineQrValidity();
        });

        static::retrieved(function (Booking $booking) {
            $booking->syncBookingStatus();
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
        $reference ??= now();

        if (blank($this->date)) {
            return 'upcoming';
        }

        $bookingDate = $this->date instanceof Carbon
            ? $this->date->copy()->startOfDay()
            : Carbon::parse((string) $this->date)->startOfDay();

        $currentDate = $reference->copy()->startOfDay();

        if ($bookingDate->gt($currentDate)) {
            return 'upcoming';
        }

        if ($bookingDate->lt($currentDate)) {
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

    public function syncBookingStatus(bool $persist = true): string
    {
        $calculatedStatus = $this->determineBookingStatus();

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
    public function determineQrValidity(?Carbon $reference = null): string
    {
        $reference ??= now();

        // Non-approved bookings are never valid
        if ($this->status !== 'approved') {
            return 'not_valid';
        }

        if (blank($this->date) || blank($this->start_time) || blank($this->end_time)) {
            return 'not_valid';
        }

        $bookingDate = $this->date instanceof Carbon
            ? $this->date->copy()->startOfDay()
            : Carbon::parse((string) $this->date)->startOfDay();

        $currentDate = $reference->copy()->startOfDay();

        // Must be today — future or past dates are not valid
        if (! $bookingDate->equalTo($currentDate)) {
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
