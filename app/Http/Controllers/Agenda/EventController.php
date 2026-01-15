<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventCategory;
use Illuminate\Http\Request;
use Carbon\Carbon;

class EventController extends Controller
{
    /**
     * Display a listing of events (for calendar API)
     */
    public function index(Request $request)
    {
        $start = $request->input('start');
        $end = $request->input('end');

        // Verificar e atualizar eventos passados automaticamente
        $this->checkPastEvents();

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
     * Verificar eventos passados e marcar como concluído
     */
    private function checkPastEvents()
    {
        $now = \Carbon\Carbon::now();
        
        Event::where('status', 'agendado')
            ->where(function($query) use ($now) {
                $query->where('end_date', '<', $now)
                      ->orWhere(function($q) use ($now) {
                          // Se não tiver end_date, usar start_date
                          $q->whereNull('end_date')
                            ->where('start_date', '<', $now);
                      });
            })
            ->update(['status' => 'concluido']);
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
            'recurrence' => 'nullable|in:daily,weekly,biweekly,monthly,yearly,custom',
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

        // Se for repetição semanal, criar múltiplas ocorrências
        if ($validated['recurrence'] === 'weekly') {
            $events = [];
            $startDate = Carbon::parse($startDateTime);
            $endDate = Carbon::parse($endDateTime);
            
            // Criar eventos para 1 ano à frente (52 semanas)
            // O evento será criado no mesmo dia da semana da data inicial
            for ($i = 0; $i < 52; $i++) {
                $eventStart = $startDate->copy()->addWeeks($i);
                $eventEnd = $endDate->copy()->addWeeks($i);
                
                $event = Event::create([
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'start_date' => $eventStart,
                    'end_date' => $eventEnd,
                    'all_day' => $allDay,
                    'recurrence' => 'weekly',
                    'visibility' => $validated['visibility'] ?? 'public',
                    'status' => 'agendado',
                    'location' => $validated['location'] ?? null,
                    'category_id' => !empty($validated['category_id']) ? $validated['category_id'] : null,
                ]);
                
                $events[] = $event->load('category');
            }
            
            return response()->json([
                'success' => true,
                'message' => count($events) . ' eventos criados com sucesso!',
                'events' => $events,
            ]);
        } elseif ($validated['recurrence'] === 'biweekly') {
            // Repetição quinzenal (a cada 2 semanas)
            $events = [];
            $startDate = Carbon::parse($startDateTime);
            $endDate = Carbon::parse($endDateTime);
            
            // Criar eventos para 1 ano à frente (26 ocorrências quinzenais)
            for ($i = 0; $i < 26; $i++) {
                $eventStart = $startDate->copy()->addWeeks($i * 2);
                $eventEnd = $endDate->copy()->addWeeks($i * 2);
                
                $event = Event::create([
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'start_date' => $eventStart,
                    'end_date' => $eventEnd,
                    'all_day' => $allDay,
                    'recurrence' => 'biweekly',
                    'visibility' => $validated['visibility'] ?? 'public',
                    'status' => 'agendado',
                    'location' => $validated['location'] ?? null,
                    'category_id' => !empty($validated['category_id']) ? $validated['category_id'] : null,
                ]);
                
                $events[] = $event->load('category');
            }
            
            return response()->json([
                'success' => true,
                'message' => count($events) . ' eventos criados com sucesso!',
                'events' => $events,
            ]);
        } else {
            // Evento único (sem repetição ou outra repetição)
            $event = Event::create([
                'title' => $validated['title'],
                'description' => $validated['description'] ?? null,
                'start_date' => $startDateTime,
                'end_date' => $endDateTime,
                'all_day' => $allDay,
                'recurrence' => $validated['recurrence'] ?? null,
                'visibility' => $validated['visibility'] ?? 'public',
                'status' => 'agendado',
                'location' => $validated['location'] ?? null,
                'category_id' => !empty($validated['category_id']) ? $validated['category_id'] : null,
            ]);

            return response()->json([
                'success' => true,
                'event' => $event->load('category'),
            ]);
        }
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
            'recurrence' => 'nullable|in:daily,weekly,biweekly,monthly,yearly,custom',
            'visibility' => 'nullable|in:public,private',
            'location' => 'nullable|string|max:255',
            'category_id' => 'nullable|exists:event_categories,id',
        ]);

        $updateAll = $request->input('update_all', false);
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

        // Se for para atualizar todas as ocorrências de um evento recorrente
        if ($updateAll && in_array($event->recurrence, ['weekly', 'biweekly'])) {
            // Identificar eventos relacionados
            $relatedEvents = Event::where('title', $event->title)
                ->where('recurrence', $event->recurrence)
                ->where('category_id', $event->category_id)
                ->where('location', $event->location)
                ->where('all_day', $event->all_day);
            
            // Se não for dia inteiro, verificar também o horário
            if (!$event->all_day) {
                $startTime = $event->start_date->format('H:i:s');
                $relatedEvents->whereRaw("TIME(start_date) = ?", [$startTime]);
            }
            
            $relatedEventsList = $relatedEvents->get();
            
            // Calcular diferença de tempo para manter o intervalo entre eventos
            $originalStart = Carbon::parse($event->start_date);
            $newStart = Carbon::parse($startDateTime);
            $timeDiff = $originalStart->diff($newStart);
            
            // Atualizar cada evento relacionado
            foreach ($relatedEventsList as $relatedEvent) {
                $relatedStart = Carbon::parse($relatedEvent->start_date);
                $relatedEnd = Carbon::parse($relatedEvent->end_date);
                
                // Aplicar a diferença de tempo mantendo o intervalo
                $newRelatedStart = $relatedStart->copy()->add($timeDiff);
                $newRelatedEnd = $relatedEnd->copy()->add($timeDiff);
                
                $relatedEvent->update([
                    'title' => $validated['title'],
                    'description' => $validated['description'] ?? null,
                    'start_date' => $newRelatedStart,
                    'end_date' => $newRelatedEnd,
                    'all_day' => $allDay,
                    'recurrence' => $validated['recurrence'] ?? $relatedEvent->recurrence,
                    'visibility' => $validated['visibility'] ?? 'public',
                    'location' => $validated['location'] ?? null,
                    'category_id' => !empty($validated['category_id']) ? $validated['category_id'] : null,
                ]);
            }
            
            return response()->json([
                'success' => true,
                'message' => count($relatedEventsList) . ' eventos atualizados com sucesso!',
                'events' => $relatedEventsList->map->load('category'),
            ]);
        } else {
            // Atualizar apenas este evento
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
                'message' => 'Evento atualizado com sucesso!',
                'event' => $event->load('category'),
            ]);
        }
    }

    /**
     * Remove the specified event
     */
    public function destroy(Request $request, Event $event)
    {
        $deleteAll = $request->input('delete_all', false);
        
        // Se for para remover todas as ocorrências de um evento recorrente
        if ($deleteAll && in_array($event->recurrence, ['weekly', 'biweekly'])) {
            // Identificar eventos relacionados (mesmo título, mesmo horário, mesma categoria, mesma localização)
            $relatedEvents = Event::where('title', $event->title)
                ->where('recurrence', $event->recurrence)
                ->where('category_id', $event->category_id)
                ->where('location', $event->location)
                ->where('all_day', $event->all_day);
            
            // Se não for dia inteiro, verificar também o horário
            if (!$event->all_day) {
                $startTime = $event->start_date->format('H:i:s');
                $relatedEvents->whereRaw("TIME(start_date) = ?", [$startTime]);
            }
            
            $count = $relatedEvents->count();
            $relatedEvents->delete();
            
            return response()->json([
                'success' => true,
                'message' => "{$count} ocorrências removidas com sucesso!",
            ]);
        } else {
            // Remover apenas esta ocorrência
            $event->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Evento removido com sucesso!',
            ]);
        }
    }
}

