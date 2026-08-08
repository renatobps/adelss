<?php

namespace App\Http\Controllers;

use App\Models\Meeting;
use App\Models\MeetingAttendance;
use App\Models\Pgi;
use App\Services\Pgis\PgiDashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MeetingController extends Controller
{
    /**
     * Formulário de nova reunião.
     */
    public function create(Pgi $pgi)
    {
        $this->authorize('manageMeetings', $pgi);

        return view('pgis.meetings.create', [
            'pgi' => $pgi,
            'suggestedDate' => $this->nextMeetingDate($pgi),
            'recurringLimit' => (int) config('pgis.recurring_limit', 12),
        ]);
    }

    /**
     * Lista de reuniões do PGI (consumo interno via JSON).
     */
    public function index(Pgi $pgi)
    {
        $this->authorize('view', $pgi);

        $meetings = $pgi->meetings()
            ->with(['attendances.member'])
            ->orderBy('meeting_date', 'desc')
            ->get();

        return response()->json($meetings);
    }

    /**
     * Cria a reunião e encaminha para a chamada.
     */
    public function store(Request $request, Pgi $pgi)
    {
        $this->authorize('manageMeetings', $pgi);

        $validated = $request->validate([
            'meeting_date' => 'required|date',
            'subject' => 'nullable|string|max:255',
            'total_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ], $this->meetingMessages());

        $meeting = Meeting::create([
            'pgi_id' => $pgi->id,
            'meeting_date' => $validated['meeting_date'],
            'subject' => $validated['subject'] ?? null,
            'total_value' => $validated['total_value'] ?? 0.00,
            'notes' => $validated['notes'] ?? null,
        ]);

        if ($request->boolean('skip_attendance')) {
            return redirect()->route('pgis.show', $pgi)
                ->with('success', 'Reunião cadastrada. A chamada segue pendente.');
        }

        return redirect()->route('pgis.meetings.attendance', [$pgi, $meeting])
            ->with('success', 'Reunião cadastrada! Registre a presença dos participantes.');
    }

    /**
     * Cria as próximas reuniões seguindo o dia da semana do PGI.
     */
    public function storeRecurring(Request $request, Pgi $pgi)
    {
        $this->authorize('manageMeetings', $pgi);

        $limit = (int) config('pgis.recurring_limit', 12);

        $validated = $request->validate([
            'start_date' => 'required|date',
            'occurrences' => "required|integer|min:1|max:{$limit}",
            'subject' => 'nullable|string|max:255',
        ], [
            'start_date.required' => 'Informe a data da primeira reunião.',
            'start_date.date' => 'A data da primeira reunião deve ser válida.',
            'occurrences.required' => 'Informe quantas reuniões devem ser criadas.',
            'occurrences.max' => "É possível criar no máximo {$limit} reuniões de uma vez.",
            'subject.max' => 'O assunto não pode ter mais de 255 caracteres.',
        ]);

        $date = Carbon::parse($validated['start_date'])->startOfDay();
        $weekday = $pgi->dayOfWeekNumber();

        if ($weekday !== null && $date->dayOfWeek !== $weekday) {
            $date = $date->next($weekday);
        }

        $created = 0;
        $skipped = 0;

        for ($i = 0; $i < (int) $validated['occurrences']; $i++) {
            $exists = $pgi->meetings()->whereDate('meeting_date', $date->toDateString())->exists();

            if ($exists) {
                $skipped++;
            } else {
                Meeting::create([
                    'pgi_id' => $pgi->id,
                    'meeting_date' => $date->toDateString(),
                    'subject' => $validated['subject'] ?? null,
                ]);
                $created++;
            }

            $date = $date->copy()->addWeek();
        }

        $message = "{$created} reunião(ões) criada(s) na recorrência semanal.";
        if ($skipped > 0) {
            $message .= " {$skipped} data(s) já possuíam reunião e foram ignoradas.";
        }

        return redirect()->route('pgis.show', $pgi)->with('success', $message);
    }

    /**
     * Detalhe da reunião: presentes, ausentes e visitantes.
     */
    public function show(Pgi $pgi, Meeting $meeting)
    {
        $this->authorize('view', $pgi);
        $this->ensureMeetingBelongsToPgi($pgi, $meeting);

        $meeting->load(['attendances.member', 'registeredBy']);
        $pgi->load('members');

        $presentIds = $meeting->presentMemberIds();

        return view('pgis.meetings.show', [
            'pgi' => $pgi,
            'meeting' => $meeting,
            'presentMembers' => $pgi->members->whereIn('id', $presentIds)->sortBy('name')->values(),
            'absentMembers' => $pgi->members->whereNotIn('id', $presentIds)->sortBy('name')->values(),
            'visitors' => $meeting->attendances->where('type', 'visitor')->values(),
        ]);
    }

    /**
     * Formulário de edição dos dados da reunião (não altera a chamada).
     */
    public function edit(Pgi $pgi, Meeting $meeting)
    {
        $this->authorize('manageMeetings', $pgi);
        $this->ensureMeetingBelongsToPgi($pgi, $meeting);

        return view('pgis.meetings.edit', compact('pgi', 'meeting'));
    }

    /**
     * Atualiza os dados da reunião preservando a lista de presença.
     */
    public function update(Request $request, Pgi $pgi, Meeting $meeting)
    {
        $this->authorize('manageMeetings', $pgi);
        $this->ensureMeetingBelongsToPgi($pgi, $meeting);

        $validated = $request->validate([
            'meeting_date' => 'required|date',
            'subject' => 'nullable|string|max:255',
            'total_value' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ], $this->meetingMessages());

        $meeting->update([
            'meeting_date' => $validated['meeting_date'],
            'subject' => $validated['subject'] ?? null,
            'total_value' => $validated['total_value'] ?? 0.00,
            'notes' => $validated['notes'] ?? null,
        ]);

        return redirect()->route('pgis.meetings.show', [$pgi, $meeting])
            ->with('success', 'Reunião atualizada com sucesso!');
    }

    /**
     * Tela de chamada, pensada para uso no celular.
     */
    public function attendance(Pgi $pgi, Meeting $meeting, PgiDashboardService $dashboard)
    {
        $this->authorize('manageMeetings', $pgi);
        $this->ensureMeetingBelongsToPgi($pgi, $meeting);

        $meeting->load('attendances');
        $pgi->load('members');

        return view('pgis.meetings.attendance', [
            'pgi' => $pgi,
            'meeting' => $meeting,
            'members' => $pgi->members->sortBy('name')->values(),
            'presentIds' => $meeting->presentMemberIds(),
            'visitors' => $meeting->attendances->where('type', 'visitor')->values(),
            'frequency' => $dashboard->memberFrequency(
                $dashboard->registeredMeetings($pgi, (int) config('pgis.attendance_window', 10))
            ),
        ]);
    }

    /**
     * Salva (ou refaz) a chamada da reunião.
     */
    public function storeAttendance(Request $request, Pgi $pgi, Meeting $meeting)
    {
        $this->authorize('manageMeetings', $pgi);
        $this->ensureMeetingBelongsToPgi($pgi, $meeting);

        $validated = $request->validate([
            'participants' => 'nullable|array',
            'participants.*' => 'integer|exists:members,id',
            'visitors' => 'nullable|array',
            'visitors.*.name' => 'nullable|string|max:255',
            'visitors.*.phone' => 'nullable|string|max:30',
            'notes' => 'nullable|string',
        ], [
            'participants.array' => 'A lista de presença é inválida.',
            'participants.*.exists' => 'Um ou mais participantes selecionados não existem.',
            'visitors.*.name.max' => 'O nome do visitante não pode ter mais de 255 caracteres.',
            'visitors.*.phone.max' => 'O telefone do visitante não pode ter mais de 30 caracteres.',
        ]);

        // Só aceita membros que realmente pertencem a este PGI.
        $memberIds = $pgi->members()
            ->whereIn('id', $validated['participants'] ?? [])
            ->pluck('id')
            ->all();

        DB::transaction(function () use ($meeting, $memberIds, $validated, $request) {
            $meeting->attendances()->delete();

            foreach ($memberIds as $memberId) {
                MeetingAttendance::create([
                    'meeting_id' => $meeting->id,
                    'member_id' => $memberId,
                    'type' => 'participant',
                ]);
            }

            foreach ($validated['visitors'] ?? [] as $visitor) {
                $name = trim((string) ($visitor['name'] ?? ''));

                if ($name === '') {
                    continue;
                }

                MeetingAttendance::create([
                    'meeting_id' => $meeting->id,
                    'visitor_name' => $name,
                    'visitor_phone' => trim((string) ($visitor['phone'] ?? '')) ?: null,
                    'type' => 'visitor',
                ]);
            }

            if ($request->has('notes')) {
                $meeting->notes = $validated['notes'] ?? null;
            }

            $meeting->attendance_registered_at = now();
            $meeting->attendance_registered_by = auth()->id();
            $meeting->save();

            $meeting->updateCounters();
        });

        return redirect()->route('pgis.meetings.show', [$pgi, $meeting])
            ->with('success', 'Chamada registrada com sucesso!');
    }

    /**
     * Remove a reunião.
     */
    public function destroy(Pgi $pgi, Meeting $meeting)
    {
        $this->authorize('manageMeetings', $pgi);
        $this->ensureMeetingBelongsToPgi($pgi, $meeting);

        $meeting->delete();

        return redirect()->route('pgis.show', $pgi)
            ->with('success', 'Reunião excluída com sucesso!');
    }

    private function ensureMeetingBelongsToPgi(Pgi $pgi, Meeting $meeting): void
    {
        abort_unless((int) $meeting->pgi_id === (int) $pgi->id, 404);
    }

    /**
     * Próxima data sugerida conforme o dia da semana do PGI.
     */
    private function nextMeetingDate(Pgi $pgi): string
    {
        $weekday = $pgi->dayOfWeekNumber();
        $today = now()->startOfDay();

        if ($weekday === null || $today->dayOfWeek === $weekday) {
            return $today->toDateString();
        }

        return $today->copy()->previous($weekday)->toDateString();
    }

    /**
     * @return array<string, string>
     */
    private function meetingMessages(): array
    {
        return [
            'meeting_date.required' => 'A data da reunião é obrigatória.',
            'meeting_date.date' => 'A data da reunião deve ser uma data válida.',
            'subject.max' => 'O assunto não pode ter mais de 255 caracteres.',
            'total_value.numeric' => 'O valor total deve ser um número.',
            'total_value.min' => 'O valor total não pode ser negativo.',
        ];
    }
}
