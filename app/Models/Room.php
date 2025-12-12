<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Room extends Model
{
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

    public function scopeOperational($query)
    {
        return $query->where('status', 'operational');
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
}
