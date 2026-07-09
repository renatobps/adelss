<?php

namespace App\Http\Controllers\Agenda;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\EventCategory;
use Illuminate\Http\Request;

class CalendarioController extends Controller
{
    /**
     * Display the calendar view.
     */
    public function index()
    {
        $this->authorize('viewAny', Event::class);
        $categories = EventCategory::orderBy('name')->get();
        return view('agenda.calendario.index', compact('categories'));
    }
}

