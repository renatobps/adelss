<?php

namespace App\Http\Controllers\Cultos;

use App\Http\Controllers\Controller;
use App\Models\ServiceReportSetting;
use Illuminate\Http\Request;

class ServiceReportSettingController extends Controller
{
    public function edit()
    {
        $this->authorize('manageSettings', \App\Models\ServiceReport::class);

        $settings = ServiceReportSetting::current();

        return view('cultos.configuracoes', compact('settings'));
    }

    public function update(Request $request)
    {
        $this->authorize('manageSettings', \App\Models\ServiceReport::class);

        $validated = $request->validate([
            'mode' => 'required|in:simples,detalhado',
            'consecutive_absences_alert_threshold' => 'nullable|integer|min:1|max:30',
            'visitor_no_return_days_alert' => 'nullable|integer|min:1|max:365',
            'auto_register_visitor_as_member' => 'nullable|boolean',
            'enable_children_count' => 'nullable|boolean',
            'enable_volunteers_count' => 'nullable|boolean',
            'enable_spiritual_decisions' => 'nullable|boolean',
        ]);

        $settings = ServiceReportSetting::current();
        $settings->update([
            'mode' => $validated['mode'],
            'consecutive_absences_alert_threshold' => $validated['consecutive_absences_alert_threshold'] ?? null,
            'visitor_no_return_days_alert' => $validated['visitor_no_return_days_alert'] ?? null,
            'auto_register_visitor_as_member' => $request->boolean('auto_register_visitor_as_member'),
            'enable_children_count' => $request->boolean('enable_children_count'),
            'enable_volunteers_count' => $request->boolean('enable_volunteers_count'),
            'enable_spiritual_decisions' => $request->boolean('enable_spiritual_decisions'),
        ]);

        return back()->with('success', 'Configurações salvas com sucesso.');
    }
}
