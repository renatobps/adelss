<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventCategory;
use Illuminate\Http\Request;

class EventController extends Controller
{
    /**
     * Display a listing of events (for calendar API)
     */
    public function index(Request $request)
    {
        $start = $request->input('start');
        $end = $request->input('end');

        $query = Event::with('category');

        if ($start && $end) {
            $query->whereBetween('start_date', [$start, $end]);
        }

        $events = $query->get();

        // Formatar eventos para FullCalendar
        $formattedEvents = $events->map(function ($event) {
            return [
                'id' => $event->id,
                'title' => $event->title,
                'start' => $event->start_date->format('Y-m-d\TH:i:s'),
                'end' => $event->end_date ? $event->end_date->format('Y-m-d\TH:i:s') : null,
                'allDay' => $event->all_day,
                'backgroundColor' => $event->category ? $event->category->color : '#0088cc',
                'borderColor' => $event->category ? $event->category->color : '#0088cc',
                'description' => $event->description,
                'location' => $event->location,
                'category_id' => $event->category_id,
            ];
        });

        return response()->json($formattedEvents);
    }

    /**
     * Store a newly created event
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'start_time' => 'nullable|string',
            'end_date' => 'nullable|date',
            'end_time' => 'nullable|string',
            'all_day' => 'nullable|boolean',
            'recurrence' => 'nullable|in:daily,weekly,monthly,yearly,custom',
            'visibility' => 'nullable|in:public,private',
            'location' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:event_categories,id',
        ]);

        $allDay = $validated['all_day'] ?? false;

        // Combinar data e hora
        $startDateTime = $validated['start_date'];
        if (!$allDay && isset($validated['start_time']) && !empty($validated['start_time'])) {
            $startDateTime .= ' ' . $validated['start_time'] . ':00';
        } else {
            $startDateTime .= ' 00:00:00';
        }

        $endDate = $validated['end_date'] ?? $validated['start_date'];
        $endDateTime = $endDate;
        if (!$allDay && isset($validated['end_time']) && !empty($validated['end_time'])) {
            $endDateTime .= ' ' . $validated['end_time'] . ':00';
        } else {
            $endDateTime .= ' 23:59:59';
        }

        $event = Event::create([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'start_date' => $startDateTime,
            'end_date' => $endDateTime,
            'all_day' => $allDay,
            'recurrence' => $validated['recurrence'] ?? null,
            'visibility' => $validated['visibility'] ?? 'public',
            'location' => $validated['location'] ?? null,
            'category_id' => !empty($validated['category_id']) ? $validated['category_id'] : null,
        ]);

        return response()->json([
            'success' => true,
            'event' => $event->load('category'),
        ]);
    }

    /**
     * Display the specified event
     */
    public function show(Event $event)
    {
        $event->load('category');
        
        return response()->json([
            'success' => true,
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'start_date' => $event->start_date->format('Y-m-d'),
                'start_time' => $event->start_date->format('H:i'),
                'end_date' => $event->end_date ? $event->end_date->format('Y-m-d') : null,
                'end_time' => $event->end_date ? $event->end_date->format('H:i') : null,
                'all_day' => $event->all_day,
                'recurrence' => $event->recurrence,
                'visibility' => $event->visibility,
                'location' => $event->location,
                'category_id' => $event->category_id,
            ]
        ]);
    }

    /**
     * Update the specified event
     */
    public function update(Request $request, Event $event)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'start_date' => 'required|date',
            'start_time' => 'nullable|string',
            'end_date' => 'nullable|date',
            'end_time' => 'nullable|string',
            'all_day' => 'nullable|boolean',
            'recurrence' => 'nullable|in:daily,weekly,monthly,yearly,custom',
            'visibility' => 'nullable|in:public,private',
            'location' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:event_categories,id',
        ]);

        $allDay = $validated['all_day'] ?? false;

        // Combinar data e hora
        $startDateTime = $validated['start_date'];
        if (!$allDay && isset($validated['start_time']) && !empty($validated['start_time'])) {
            $startDateTime .= ' ' . $validated['start_time'] . ':00';
        } else {
            $startDateTime .= ' 00:00:00';
        }

        $endDate = $validated['end_date'] ?? $validated['start_date'];
        $endDateTime = $endDate;
        if (!$allDay && isset($validated['end_time']) && !empty($validated['end_time'])) {
            $endDateTime .= ' ' . $validated['end_time'] . ':00';
        } else {
            $endDateTime .= ' 23:59:59';
        }

        $event->update([
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'start_date' => $startDateTime,
            'end_date' => $endDateTime,
            'all_day' => $allDay,
            'recurrence' => $validated['recurrence'] ?? null,
            'visibility' => $validated['visibility'] ?? 'public',
            'location' => $validated['location'] ?? null,
            'category_id' => !empty($validated['category_id']) ? $validated['category_id'] : null,
        ]);

        return response()->json([
            'success' => true,
            'event' => $event->load('category'),
        ]);
    }

    /**
     * Remove the specified event
     */
    public function destroy(Event $event)
    {
        $event->delete();

        return response()->json([
            'success' => true,
        ]);
    }
}

