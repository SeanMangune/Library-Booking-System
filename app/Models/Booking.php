<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

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
        'has_conflict',
        'conflicts_with',
        'reason',
    ];

    protected $casts = [
        'date' => 'date',
        'has_conflict' => 'boolean',
    ];

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
