<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Support\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgendaEventController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $from = $this->parseDate($request->query('start')) ?? now()->startOfDay();
        $until = $this->parseDate($request->query('end')) ?? now()->addDays(45)->endOfDay();

        $events = Event::query()
            ->with('category:id,name,color')
            ->where('status', '!=', 'cancelado')
            ->whereBetween('start_date', [$from, $until])
            ->orderBy('start_date')
            ->get()
            ->map(fn (Event $event) => $this->payload($event));

        return ApiResponse::success($events->all(), [
            'start' => $from->toIso8601String(),
            'end' => $until->toIso8601String(),
        ]);
    }

    public function show(Event $event): JsonResponse
    {
        if ($event->status === 'cancelado') {
            return ApiResponse::error('Evento não encontrado.', 404);
        }

        $event->loadMissing('category:id,name,color');

        return ApiResponse::success($this->payload($event));
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(Event $event): array
    {
        return [
            'id' => $event->id,
            'title' => $event->title,
            'description' => $event->description,
            'starts_at' => $event->start_date?->toIso8601String(),
            'ends_at' => $event->end_date?->toIso8601String(),
            'all_day' => (bool) $event->all_day,
            'location' => $event->location,
            'visibility' => $event->visibility,
            'status' => $event->status,
            'category' => $event->category ? [
                'id' => $event->category->id,
                'name' => $event->category->name,
                'color' => $event->category->color,
            ] : null,
        ];
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
