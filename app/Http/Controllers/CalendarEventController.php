<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CalendarEventController extends Controller
{
    /**
     * List events (JSON) — used by calendar feed.
     */
    public function index(Request $request): JsonResponse
    {
        $query = CalendarEvent::query();

        if ($request->filled('start') && $request->filled('end')) {
            $query->whereBetween('date', [
                Carbon::parse($request->start)->format('Y-m-d'),
                Carbon::parse($request->end)->format('Y-m-d'),
            ]);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $events = $query->orderBy('date')->orderBy('start_time')->get();

        return response()->json($events->map(function (CalendarEvent $event) {
            $color = $event->resolvedColor();
            $dateStr = $event->date->format('Y-m-d');

            $data = [
                'id' => 'evt-' . $event->id,
                'title' => $event->title,
                'backgroundColor' => $color,
                'borderColor' => $color,
                'allDay' => $event->is_all_day,
                'extendedProps' => [
                    'event_id' => $event->id,
                    'is_calendar_event' => true,
                    'type' => $event->type,
                    'type_label' => $event->typeLabel(),
                    'description' => $event->description,
                    'source' => $event->source,
                    'is_all_day' => $event->is_all_day,
                    'color' => $color,
                ],
            ];

            if ($event->is_all_day) {
                $data['start'] = $dateStr;
                $data['end'] = $event->date->copy()->addDay()->format('Y-m-d');
            } else {
                $startTime = $event->start_time ? Carbon::parse($event->start_time)->format('H:i:s') : '08:00:00';
                $endTime = $event->end_time ? Carbon::parse($event->end_time)->format('H:i:s') : '17:00:00';
                $data['start'] = $dateStr . 'T' . $startTime;
                $data['end'] = $dateStr . 'T' . $endTime;
            }

            return $data;
        }));
    }

    /**
     * Store a new event (admin only).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'type' => 'required|in:holiday,online_class,school_event,custom',
            'date' => 'required|date',
            'is_all_day' => 'boolean',
            'start_time' => 'nullable|required_if:is_all_day,false',
            'end_time' => 'nullable|required_if:is_all_day,false|after:start_time',
            'color' => 'nullable|string|max:20',
        ]);

        $validated['is_all_day'] = $validated['is_all_day'] ?? true;
        $validated['source'] = 'manual';
        $validated['created_by'] = $request->user()?->id;

        if ($validated['is_all_day']) {
            $validated['start_time'] = null;
            $validated['end_time'] = null;
        }

        $event = CalendarEvent::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Event created successfully.',
            'event' => $event,
        ], 201);
    }

    /**
     * Update an existing event (admin only).
     */
    public function update(Request $request, CalendarEvent $calendarEvent): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'type' => 'required|in:holiday,online_class,school_event,custom',
            'date' => 'required|date',
            'is_all_day' => 'boolean',
            'start_time' => 'nullable|required_if:is_all_day,false',
            'end_time' => 'nullable|required_if:is_all_day,false|after:start_time',
            'color' => 'nullable|string|max:20',
        ]);

        $validated['is_all_day'] = $validated['is_all_day'] ?? true;

        if ($validated['is_all_day']) {
            $validated['start_time'] = null;
            $validated['end_time'] = null;
        }

        $calendarEvent->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Event updated successfully.',
            'event' => $calendarEvent->fresh(),
        ]);
    }

    /**
     * Delete an event (admin only).
     */
    public function destroy(CalendarEvent $calendarEvent): JsonResponse
    {
        $calendarEvent->delete();

        return response()->json([
            'success' => true,
            'message' => 'Event deleted successfully.',
        ]);
    }
}
