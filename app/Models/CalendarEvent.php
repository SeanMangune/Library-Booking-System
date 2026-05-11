<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarEvent extends Model
{
    protected $fillable = [
        'title',
        'description',
        'type',
        'date',
        'start_time',
        'end_time',
        'is_all_day',
        'color',
        'source',
        'source_id',
        'is_recurring',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'is_all_day' => 'boolean',
        'is_recurring' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Default color per event type.
     */
    public function resolvedColor(): string
    {
        if (! empty($this->color)) {
            return $this->color;
        }

        return match ($this->type) {
            'holiday' => '#3B82F6',       // blue
            'online_class' => '#8B5CF6',  // purple
            'school_event' => '#F59E0B',  // amber
            default => '#6366F1',          // indigo
        };
    }

    /**
     * Human-readable type label.
     */
    public function typeLabel(): string
    {
        return match ($this->type) {
            'holiday' => 'Holiday',
            'online_class' => 'Online Class',
            'school_event' => 'School Event',
            'custom' => 'Custom Event',
            default => ucfirst(str_replace('_', ' ', $this->type)),
        };
    }
}
