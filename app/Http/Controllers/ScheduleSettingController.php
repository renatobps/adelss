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
        $serviceAreas = ServiceArea::where('status', 'ativo')->orderBy('name')->get();

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

        return back()->with('success', 'Configurações de escala salvas com sucesso.');
    }
}
