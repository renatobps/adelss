<?php

namespace App\Http\Controllers;

use App\Models\ScheduleNotificationSetting;
use App\Models\ServiceArea;
use App\Models\ServiceSchedule;
use Illuminate\Http\Request;

class ScheduleSettingController extends Controller
{
    public function edit()
    {
        $this->authorize('update', new ServiceSchedule());

        $settings = ScheduleNotificationSetting::current();
        $serviceAreas = ServiceArea::query()
            ->with(['children' => function ($query) {
                $query->orderBy('sort_order')->orderBy('name');
            }])
            ->roots()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('monthly-culto-schedules.settings', compact('settings', 'serviceAreas'));
    }

    public function update(Request $request)
    {
        $this->authorize('update', new ServiceSchedule());

        foreach (['month_time', 'week_time', 'day_time'] as $timeField) {
            $value = (string) $request->input($timeField, '');
            if (preg_match('/^(\d{2}:\d{2})/', $value, $matches)) {
                $request->merge([$timeField => $matches[1]]);
            }
        }

        $validated = $request->validate([
            'month_enabled' => 'nullable|boolean',
            'month_day' => 'required|integer|min:1|max:28',
            'month_time' => 'required|date_format:H:i',
            'month_template' => 'nullable|string|max:4000',
            'week_enabled' => 'nullable|boolean',
            'week_weekday' => 'required|integer|min:1|max:7',
            'week_time' => 'required|date_format:H:i',
            'week_template' => 'nullable|string|max:4000',
            'day_enabled' => 'nullable|boolean',
            'day_time' => 'required|date_format:H:i',
            'day_template' => 'nullable|string|max:4000',
            'quantities' => 'nullable|array',
            'quantities.*' => 'required|integer|min:1|max:20',
            'subarea_names' => 'nullable|array',
            'subarea_names.*' => 'nullable|string|max:150',
        ], [
            'month_day.required' => 'Informe o dia do mês para o alerta.',
            'week_weekday.required' => 'Informe o dia da semana para o alerta.',
            'quantities.*.min' => 'Cada área precisa de pelo menos 1 pessoa.',
        ]);

        $settings = ScheduleNotificationSetting::current();
        $settings->update([
            'month_enabled' => $request->boolean('month_enabled'),
            'month_day' => (int) $validated['month_day'],
            'month_time' => $validated['month_time'],
            'month_template' => $validated['month_template'] ?: $settings->month_template,
            'week_enabled' => $request->boolean('week_enabled'),
            'week_weekday' => (int) $validated['week_weekday'],
            'week_time' => $validated['week_time'],
            'week_template' => $validated['week_template'] ?: $settings->week_template,
            'day_enabled' => $request->boolean('day_enabled'),
            'day_time' => $validated['day_time'],
            'day_template' => $validated['day_template'] ?: $settings->day_template,
        ]);

        foreach ($validated['quantities'] ?? [] as $areaId => $quantity) {
            ServiceArea::where('id', (int) $areaId)->update([
                'min_quantity' => (int) $quantity,
            ]);
        }

        foreach ($validated['subarea_names'] ?? [] as $areaId => $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }

            ServiceArea::where('id', (int) $areaId)
                ->whereNotNull('parent_id')
                ->update(['name' => $name]);
        }

        return back()->with('success', 'Configurações de escala salvas com sucesso.');
    }

    public function storeSubarea(Request $request)
    {
        $this->authorize('update', new ServiceSchedule());

        $validated = $request->validate([
            'parent_id' => 'required|exists:service_areas,id',
            'name' => 'required|string|max:150',
            'min_quantity' => 'required|integer|min:1|max:20',
        ], [
            'parent_id.required' => 'Selecione a escala pai.',
            'name.required' => 'Informe o nome da subárea.',
            'min_quantity.min' => 'A subárea precisa de pelo menos 1 pessoa.',
        ]);

        $parent = ServiceArea::query()->roots()->findOrFail($validated['parent_id']);
        $sortOrder = (int) $parent->children()->max('sort_order') + 1;

        ServiceArea::create([
            'parent_id' => $parent->id,
            'name' => trim($validated['name']),
            'status' => 'ativo',
            'min_quantity' => (int) $validated['min_quantity'],
            'sort_order' => $sortOrder,
            'allowed_audience' => $parent->allowed_audience ?: 'ambos',
            'leader_id' => $parent->leader_id,
        ]);

        return back()->with('success', "Subárea \"{$validated['name']}\" adicionada em {$parent->name}.");
    }

    public function destroySubarea(ServiceArea $area)
    {
        $this->authorize('update', new ServiceSchedule());

        if (! $area->parent_id) {
            return back()->with('error', 'Só é possível remover subáreas.');
        }

        $name = $area->name;
        $area->delete();

        return back()->with('success', "Subárea \"{$name}\" removida.");
    }
}
