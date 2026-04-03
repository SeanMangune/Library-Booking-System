<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class Room extends Model
{
    private const EXCLUDED_ROOM_SLUG_PREFIXES = [
        'conference-room',
        'library-room',
    ];

    private const EXCLUDED_ROOM_NAME_PATTERNS = [
        'conference room',
        'library room',
    ];

    protected $fillable = [
        'name',
        'slug',
        'capacity',
        'description',
        'location',
        'status',
        'requires_approval',
        'status_start_at',
        'status_end_at',
    ];

    protected $casts = [
        'requires_approval' => 'boolean',
        'status_start_at' => 'datetime',
        'status_end_at' => 'datetime',
    ];

    public function setRequiresApprovalAttribute($value): void
    {
        $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        $boolValue = $normalized ?? false;

        // PostgreSQL rejects integer bindings for boolean columns in this flow.
        if (DB::connection()->getDriverName() === 'pgsql') {
            $this->attributes['requires_approval'] = $boolValue ? 'true' : 'false';

            return;
        }

        $this->attributes['requires_approval'] = $boolValue;
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function approvedBookings(): HasMany
    {
        return $this->hasMany(Booking::class)->where('status', 'approved');
    }

    public function todayBookings(): HasMany
    {
        return $this->hasMany(Booking::class)
            ->whereDate('date', today())
            ->where('status', 'approved');
    }

    public function upcomingBookings(): HasMany
    {
        return $this->hasMany(Booking::class)
            ->whereDate('date', '>', today())
            ->where('status', 'approved')
            ->orderBy('date')
            ->orderBy('start_time');
    }

    public function isOperational(): bool
    {
        return $this->status === 'operational';
    }

    public function isCollaborative(): bool
    {
        $value = Str::lower(trim(($this->name ?? '') . ' ' . ($this->slug ?? '')));

        return Str::contains($value, ['collaborative', 'collab']);
    }

    public function standardBookingCapacityLimit(): int
    {
        if (! $this->isCollaborative()) {
            return max(1, (int) $this->capacity);
        }

        // Collaborative rooms default to a fixed base capacity of 10.
        return 10;
    }

    public function maxStudentBookingCapacity(): int
    {
        if (! $this->isCollaborative()) {
            return max(1, (int) $this->capacity);
        }

        // Collaborative-room requests may be extended up to 12 by librarian approval.
        return 12;
    }

    public function absoluteBookingCapacityLimit(): int
    {
        if ($this->isCollaborative()) {
            return 12;
        }

        return max(1, (int) $this->capacity);
    }

    public function requiresCapacityPermissionFor(int $attendees, ?User $user = null): bool
    {
        // Collaborative-room requests above the base 10 always require librarian approval.
        return $this->isCollaborative() && $attendees > $this->standardBookingCapacityLimit();
    }

    public function exceedsBookingLimitFor(int $attendees, ?User $user = null): bool
    {
        $limit = $this->isCollaborative()
            ? $this->absoluteBookingCapacityLimit()
            : ($user?->isStaff() ? max(1, (int) $this->capacity) : $this->maxStudentBookingCapacity());

        return $attendees > $limit;
    }

    public function scopeOperational($query)
    {
        return $query->visible()->where('status', 'operational');
    }

    public function scopeByStatus($query, $status)
    {
        if ($status && $status !== 'all') {
            return $query->where('status', $status);
        }
        return $query;
    }

    public function scopeByCapacity($query, $capacity)
    {
        if ($capacity && $capacity !== 'all') {
            return $query->where('capacity', '>=', $capacity);
        }
        return $query;
    }

    public function scopeByLocation($query, $location)
    {
        if ($location && $location !== 'all') {
            return $query->where('location', $location);
        }
        return $query;
    }

    public function scopeVisible($query)
    {
        return $query
            // Exclude exact and suffixed slugs (e.g., conference-room-abcde)
            ->whereRaw("LOWER(COALESCE(slug, '')) NOT LIKE ?", [self::EXCLUDED_ROOM_SLUG_PREFIXES[0] . '%'])
            ->whereRaw("LOWER(COALESCE(slug, '')) NOT LIKE ?", [self::EXCLUDED_ROOM_SLUG_PREFIXES[1] . '%'])
            // Exclude names with spacing/casing variants and extended labels
            ->whereRaw("LOWER(TRIM(COALESCE(name, ''))) NOT LIKE ?", ['%' . self::EXCLUDED_ROOM_NAME_PATTERNS[0] . '%'])
            ->whereRaw("LOWER(TRIM(COALESCE(name, ''))) NOT LIKE ?", ['%' . self::EXCLUDED_ROOM_NAME_PATTERNS[1] . '%'])
            // Catch non-standard separators/spaces like "conference  room" or "library-room"
            ->whereRaw("LOWER(TRIM(COALESCE(name, ''))) NOT LIKE ?", ['%conference%room%'])
            ->whereRaw("LOWER(TRIM(COALESCE(name, ''))) NOT LIKE ?", ['%library%room%']);
    }

    public function isExcludedRoom(): bool
    {
        $normalizedName = Str::of((string) $this->name)->lower()->squish()->value();
        $normalizedSlug = Str::of((string) $this->slug)->lower()->trim()->value();

        $looksLikeConferenceRoom = Str::contains($normalizedName, 'conference') && Str::contains($normalizedName, 'room');
        $looksLikeLibraryRoom = Str::contains($normalizedName, 'library') && Str::contains($normalizedName, 'room');

        return Str::contains($normalizedName, self::EXCLUDED_ROOM_NAME_PATTERNS)
            || $looksLikeConferenceRoom
            || $looksLikeLibraryRoom
            || Str::startsWith($normalizedSlug, self::EXCLUDED_ROOM_SLUG_PREFIXES);
    }
}
