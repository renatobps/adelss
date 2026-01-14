<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingAttendance;
use App\Models\Pgi;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeetingController extends Controller
{
    /**
     * Show the form for creating a new resource.
     */
    public function create(Pgi $pgi)
    {
        $members = $pgi->members()->orderBy('name')->get();
        return view('meetings.create', compact('pgi', 'members'));
    }

    /**
     * Display a listing of the resource for a PGI.
     */
    public function index(Pgi $pgi)
    {
        $meetings = $pgi->meetings()
            ->with(['attendances.member'])
            ->orderBy('meeting_date', 'desc')
            ->get();

        return response()->json($meetings);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Pgi $pgi)
    {
        $validated = $request->validate([
            'meeting_date' => 'required|date',
            'subject' => 'nullable|string|max:255',
            'total_value' => 'nullable|numeric|min:0',
            'participants' => 'nullable|array',
            'participants.*' => 'exists:members,id',
            'visitors' => 'nullable|array',
            'visitors.*.name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ], [
            'meeting_date.required' => 'A data da reunião é obrigatória.',
            'meeting_date.date' => 'A data da reunião deve ser uma data válida.',
            'subject.max' => 'O assunto não pode ter mais de 255 caracteres.',
            'total_value.numeric' => 'O valor total deve ser um número.',
            'total_value.min' => 'O valor total não pode ser negativo.',
            'participants.array' => 'Os participantes devem ser uma lista válida.',
            'participants.*.exists' => 'Um ou mais participantes selecionados não existem.',
            'visitors.array' => 'Os visitantes devem ser uma lista válida.',
            'visitors.*.name.string' => 'O nome do visitante deve ser um texto.',
            'visitors.*.name.max' => 'O nome do visitante não pode ter mais de 255 caracteres.',
        ]);

        $meeting = Meeting::create([
            'pgi_id' => $pgi->id,
            'meeting_date' => $validated['meeting_date'],
            'subject' => $validated['subject'] ?? null,
            'total_value' => $validated['total_value'] ?? 0.00,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Adicionar participantes
        if (isset($validated['participants']) && is_array($validated['participants'])) {
            foreach ($validated['participants'] as $memberId) {
                if (!empty($memberId)) {
                    MeetingAttendance::create([
                        'meeting_id' => $meeting->id,
                        'member_id' => $memberId,
                        'type' => 'participant',
                    ]);
                }
            }
        }

        // Adicionar visitantes
        if (isset($validated['visitors']) && is_array($validated['visitors'])) {
            foreach ($validated['visitors'] as $visitor) {
                if (isset($visitor['name']) && !empty(trim($visitor['name']))) {
                    MeetingAttendance::create([
                        'meeting_id' => $meeting->id,
                        'visitor_name' => trim($visitor['name']),
                        'type' => 'visitor',
                    ]);
                }
            }
        }

        // Atualizar contadores
        $meeting->updateCounters();

        return redirect()->route('pgis.show', $pgi)
            ->with('success', 'Reunião cadastrada com sucesso!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pgi $pgi, Meeting $meeting)
    {
        $members = $pgi->members()->orderBy('name')->get();
        $meeting->load(['attendances.member']);
        return view('meetings.edit', compact('pgi', 'meeting', 'members'));
    }

    /**
     * Display the specified resource.
     */
    public function show(Meeting $meeting)
    {
        $meeting->load(['pgi', 'attendances.member']);
        return response()->json($meeting);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pgi $pgi, Meeting $meeting)
    {
        $validated = $request->validate([
            'meeting_date' => 'required|date',
            'subject' => 'nullable|string|max:255',
            'total_value' => 'nullable|numeric|min:0',
            'participants' => 'nullable|array',
            'participants.*' => 'exists:members,id',
            'visitors' => 'nullable|array',
            'visitors.*.name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ], [
            'meeting_date.required' => 'A data da reunião é obrigatória.',
            'meeting_date.date' => 'A data da reunião deve ser uma data válida.',
            'subject.max' => 'O assunto não pode ter mais de 255 caracteres.',
            'total_value.numeric' => 'O valor total deve ser um número.',
            'total_value.min' => 'O valor total não pode ser negativo.',
            'participants.array' => 'Os participantes devem ser uma lista válida.',
            'participants.*.exists' => 'Um ou mais participantes selecionados não existem.',
            'visitors.array' => 'Os visitantes devem ser uma lista válida.',
            'visitors.*.name.string' => 'O nome do visitante deve ser um texto.',
            'visitors.*.name.max' => 'O nome do visitante não pode ter mais de 255 caracteres.',
        ]);

        $meeting->update([
            'meeting_date' => $validated['meeting_date'],
            'subject' => $validated['subject'] ?? null,
            'total_value' => $validated['total_value'] ?? 0.00,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Remover presenças antigas
        $meeting->attendances()->delete();

        // Adicionar participantes
        if (isset($validated['participants']) && is_array($validated['participants'])) {
            foreach ($validated['participants'] as $memberId) {
                if (!empty($memberId)) {
                    MeetingAttendance::create([
                        'meeting_id' => $meeting->id,
                        'member_id' => $memberId,
                        'type' => 'participant',
                    ]);
                }
            }
        }

        // Adicionar visitantes
        if (isset($validated['visitors']) && is_array($validated['visitors'])) {
            foreach ($validated['visitors'] as $visitor) {
                if (isset($visitor['name']) && !empty(trim($visitor['name']))) {
                    MeetingAttendance::create([
                        'meeting_id' => $meeting->id,
                        'visitor_name' => trim($visitor['name']),
                        'type' => 'visitor',
                    ]);
                }
            }
        }

        // Atualizar contadores
        $meeting->updateCounters();

        return redirect()->route('pgis.show', $pgi)
            ->with('success', 'Reunião atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pgi $pgi, Meeting $meeting)
    {
        $meeting->delete();

        return redirect()->route('pgis.show', $pgi)
            ->with('success', 'Reunião excluída com sucesso!');
    }
}

