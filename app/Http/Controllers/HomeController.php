<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Event;
use App\Models\HomePageSetting;
use Carbon\Carbon;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $settings = HomePageSetting::current();

        $departments = Department::active()
            ->where('show_on_homepage', true)
            ->orderBy('homepage_order')
            ->orderBy('name')
            ->get();

        $weeklyAgenda = collect();
        $monthlyEvents = collect();
        $weekLabel = '';
        $monthLabel = '';
        $nextCulto = Event::query()
            ->with('category')
            ->where('visibility', 'public')
            ->where('start_date', '>=', now())
            ->whereHas('category', fn ($q) => $q->whereRaw('LOWER(name) = ?', ['culto']))
            ->orderBy('start_date')
            ->first();
        $homeAddress = 'df473 chacara via sacra lotes 16/17';

        if ($settings->show_events_section) {
            $now = now();
            Carbon::setLocale('pt_BR');

            // Regra da agenda semanal: segunda (início) até domingo (fim).
            $weekStart = $now->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
            $weekEnd = $now->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();
            $monthStart = $now->copy()->startOfMonth()->startOfDay();
            $monthEnd = $now->copy()->endOfMonth()->endOfDay();

            $weekLabel = $weekStart->translatedFormat('d M')
                .' – '
                .$weekEnd->translatedFormat('d M');
            $monthLabel = $now->translatedFormat('F Y');

            $weeklyAgenda = Event::query()
                ->with('category')
                ->where('visibility', 'public')
                ->agendaSemanal()
                ->whereBetween('start_date', [$weekStart, $weekEnd])
                ->where('start_date', '>=', $now)
                ->orderBy('start_date')
                ->get();

            $monthlyLimit = max(1, min(24, (int) $settings->events_count));

            $monthlyEvents = Event::query()
                ->with('category')
                ->where('visibility', 'public')
                ->eventosDoMes()
                ->whereBetween('start_date', [$monthStart, $monthEnd])
                ->where('start_date', '>=', $now)
                ->orderBy('start_date')
                ->limit($monthlyLimit)
                ->get();
        }

        return view('site.home', compact(
            'settings',
            'departments',
            'weeklyAgenda',
            'monthlyEvents',
            'weekLabel',
            'monthLabel',
            'nextCulto',
            'homeAddress',
        ));
    }
}
