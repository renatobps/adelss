<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DepartmentEvent;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DepartmentEventController extends Controller
{
    // Events follow the existing department CRUD permissions.
    public function index(Request $request, Department $department)
    {
        $this->authorize('view', $department);
        $validated = $request->validate([
            'year' => 'sometimes|required|integer|min:1900|max:9998',
            'view' => 'sometimes|required|in:calendar,list',
        ]);
        $display = $validated['view'] ?? 'calendar';
        $year = (int) ($validated['year'] ?? now()->year);
        $start = CarbonImmutable::create($year, 1, 1)->startOfDay();
        $end = $start->addYear();
        $events = $department->events()
            ->where('start_date', '<', $end)
            ->where(function ($query) use ($start) {
                $query->where('end_date', '>=', $start)
                    ->orWhere(function ($query) use ($start) {
                        $query->whereNull('end_date')->where('start_date', '>=', $start);
                    });
            })
            ->orderBy('start_date')->orderBy('id')->get();

        $months = collect(range(1, 12))->map(function ($month) use ($start, $events) {
            $date = $start->month($month);

            return [
                'date' => $date,
                'events' => $events->filter(fn ($event) => $event->start_date->lt($date->addMonth())
                    && ($event->end_date ?? $event->start_date)->gte($date)),
            ];
        });

        return view('departments.events.index', compact('department', 'year', 'events', 'months', 'display'));
    }

    public function create(Request $request, Department $department)
    {
        $this->authorize('create', Department::class);
        $validated = $request->validate(['date' => 'nullable|date_format:Y-m-d|after_or_equal:1900-01-01|before:9999-01-01']);
        $event = new DepartmentEvent(['start_date' => ($validated['date'] ?? now()->toDateString()).' 09:00:00']);

        return view('departments.events.form', compact('department', 'event'));
    }

    public function store(Request $request, Department $department)
    {
        $this->authorize('create', Department::class);
        $event = new DepartmentEvent();
        $event->department_id = $department->id;
        $this->saveEvent($request, $event);

        return redirect()->route('departments.events.show', [$department, $event])
            ->with('success', 'Evento cadastrado com sucesso!');
    }

    public function show(Department $department, DepartmentEvent $event)
    {
        $this->authorize('view', $department);

        return view('departments.events.show', compact('department', 'event'));
    }

    public function edit(Department $department, DepartmentEvent $event)
    {
        $this->authorize('update', $department);

        return view('departments.events.form', compact('department', 'event'));
    }

    public function update(Request $request, Department $department, DepartmentEvent $event)
    {
        $this->authorize('update', $department);
        $this->saveEvent($request, $event);

        return redirect()->route('departments.events.show', [$department, $event])
            ->with('success', 'Evento atualizado com sucesso!');
    }

    public function destroy(Department $department, DepartmentEvent $event)
    {
        $this->authorize('delete', $department);
        $year = $event->start_date->year;
        $banner = $event->banner_image;
        $event->delete();
        if ($banner) {
            Storage::disk('public')->delete($banner);
        }

        return redirect()->route('departments.events.index', [$department, 'year' => $year])
            ->with('success', 'Evento excluído com sucesso!');
    }

    private function validatedEvent(Request $request): array
    {
        // Accept both Brazilian currency input and decimal values from API clients.
        $cost = $request->input('cost');
        if (is_string($cost)) {
            $cost = trim($cost);
            if (preg_match('/^(?:\d{1,3}(?:\.\d{3})+|\d+),\d{1,2}$/D', $cost)) {
                $cost = str_replace(',', '.', str_replace('.', '', $cost));
            }
            $request->merge(['cost' => $cost === '' ? null : $cost]);
        }
        $data = $request->validate([
            'title' => 'required|string|max:191',
            'description' => 'nullable|string|max:10000',
            'location' => 'nullable|string|max:191',
            'start_date' => 'required|date_format:Y-m-d\TH:i|after_or_equal:1900-01-01|before:9999-01-01',
            'end_date' => 'nullable|date_format:Y-m-d\TH:i|after_or_equal:1900-01-01|before:9999-01-01',
            'all_day' => 'sometimes|boolean',
            'cost' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99', 'regex:/^\d+(?:\.\d{1,2})?$/D'],
            'banner_image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'remove_banner' => 'sometimes|boolean',
        ]);
        $data['all_day'] = $request->boolean('all_day');
        if ($data['all_day']) {
            $data['start_date'] = CarbonImmutable::parse($data['start_date'])->startOfDay();
            $data['end_date'] = CarbonImmutable::parse($data['end_date'] ?? $data['start_date'])->endOfDay();
        }
        if (!empty($data['end_date']) && CarbonImmutable::parse($data['end_date'])->lt(CarbonImmutable::parse($data['start_date']))) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'end_date' => 'O término deve ser igual ou posterior ao início.',
            ]);
        }

        return $data;
    }

    private function saveEvent(Request $request, DepartmentEvent $event): void
    {
        $data = $this->validatedEvent($request);
        unset($data['banner_image'], $data['remove_banner']);
        $oldBanner = $event->banner_image;
        $newBanner = null;
        if ($request->hasFile('banner_image')) {
            $newBanner = $request->file('banner_image')->store('departments/events/banners', 'public');
            if (!$newBanner) {
                throw new \RuntimeException('Não foi possível salvar o banner do evento.');
            }
            $data['banner_image'] = $newBanner;
        } elseif ($request->boolean('remove_banner')) {
            $data['banner_image'] = null;
        }

        try {
            $event->fill($data)->saveOrFail();
        } catch (\Throwable $exception) {
            if ($newBanner) {
                Storage::disk('public')->delete($newBanner);
            }
            throw $exception;
        }

        if ($oldBanner && $oldBanner !== $event->banner_image) {
            Storage::disk('public')->delete($oldBanner);
        }
    }
}
