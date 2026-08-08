<?php

namespace App\Services\Pgis;

use App\Models\Meeting;
use App\Models\Pgi;
use Illuminate\Support\Collection;

class PgiDashboardService
{
    /**
     * Reuniões já com chamada feita, da mais recente para a mais antiga.
     */
    public function registeredMeetings(Pgi $pgi, ?int $limit = null): Collection
    {
        $query = $pgi->meetings()
            ->whereNotNull('attendance_registered_at')
            ->with('attendances')
            ->orderByDesc('meeting_date')
            ->orderByDesc('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Frequência de cada membro nas reuniões informadas.
     *
     * @return array<int, array{present: int, total: int}>
     */
    public function memberFrequency(Collection $meetings): array
    {
        $total = $meetings->count();

        if ($total === 0) {
            return [];
        }

        $frequency = [];

        foreach ($meetings as $meeting) {
            foreach ($meeting->presentMemberIds() as $memberId) {
                $frequency[$memberId] = ($frequency[$memberId] ?? 0) + 1;
            }
        }

        return collect($frequency)
            ->map(fn (int $present) => ['present' => $present, 'total' => $total])
            ->all();
    }

    /**
     * Membros ausentes nas últimas N reuniões consecutivas com chamada feita.
     *
     * Mesma regra usada nos alertas pastorais dos Relatórios de Culto: só alerta
     * quando existem pelo menos N reuniões avaliadas e o membro faltou em todas.
     *
     * @return Collection<int, \App\Models\Member>
     */
    public function consecutiveAbsentees(Pgi $pgi, int $threshold): Collection
    {
        $threshold = max(1, $threshold);
        $meetings = $this->registeredMeetings($pgi, $threshold);

        if ($meetings->count() < $threshold) {
            return collect();
        }

        $presentIds = $meetings
            ->flatMap(fn (Meeting $meeting) => $meeting->presentMemberIds())
            ->unique()
            ->all();

        return $pgi->members
            ->reject(fn ($member) => in_array((int) $member->id, $presentIds, true))
            ->values();
    }

    /**
     * Membros ausentes na última reunião com chamada feita.
     *
     * @return Collection<int, \App\Models\Member>
     */
    public function lastMeetingAbsentees(Pgi $pgi): Collection
    {
        $meeting = $this->registeredMeetings($pgi, 1)->first();

        if (! $meeting) {
            return collect();
        }

        $presentIds = $meeting->presentMemberIds();

        return $pgi->members
            ->reject(fn ($member) => in_array((int) $member->id, $presentIds, true))
            ->values();
    }

    /**
     * Indicadores exibidos no topo da página do PGI.
     *
     * @return array<string, int|float|null>
     */
    public function kpis(Pgi $pgi, Collection $windowMeetings): array
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $meetingsThisMonth = $pgi->meetings()
            ->whereBetween('meeting_date', [$monthStart, $monthEnd])
            ->count();

        $visitorsThisMonth = (int) $pgi->meetings()
            ->whereBetween('meeting_date', [$monthStart, $monthEnd])
            ->sum('visitors_count');

        $averageAttendance = $windowMeetings->isNotEmpty()
            ? round($windowMeetings->avg('participants_count'), 1)
            : null;

        return [
            'members' => $pgi->members->count(),
            'average_attendance' => $averageAttendance,
            'average_meetings' => $windowMeetings->count(),
            'meetings_this_month' => $meetingsThisMonth,
            'visitors_this_month' => $visitorsThisMonth,
            'pending_attendance' => $pgi->meetings()->whereNull('attendance_registered_at')->count(),
        ];
    }

    /**
     * Série do gráfico de presenças. Vazia quando nenhuma chamada foi registrada.
     *
     * @return array<int, array{date: string, participants: int, visitors: int, total: int}>
     */
    public function chartData(Pgi $pgi, int $limit = 12): array
    {
        return $this->registeredMeetings($pgi, $limit)
            ->sortBy([['meeting_date', 'asc'], ['id', 'asc']])
            ->map(fn (Meeting $meeting) => [
                'date' => $meeting->meeting_date->format('d/m'),
                'participants' => (int) $meeting->participants_count,
                'visitors' => (int) $meeting->visitors_count,
                'total' => (int) $meeting->participants_count + (int) $meeting->visitors_count,
            ])
            ->values()
            ->all();
    }
}
