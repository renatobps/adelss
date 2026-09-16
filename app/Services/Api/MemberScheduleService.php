<?php

namespace App\Services\Api;

use App\Models\Member;
use App\Models\MonthlyCultoSchedule;
use App\Models\MoriahSchedule;
use App\Models\ServiceArea;
use App\Models\ServiceScheduleVolunteer;
use App\Models\Volunteer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MemberScheduleService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function upcoming(Member $member, ?Carbon $from = null, ?Carbon $until = null): array
    {
        $from = ($from ?? now())->copy()->startOfDay();
        $until = $until?->copy()->endOfDay();

        return collect()
            ->concat($this->moriahItems($member, $from, $until))
            ->concat($this->serviceItems($member, $from, $until))
            ->concat($this->monthlyItems($member, $from, $until))
            ->sortBy('starts_at')
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public function confirmService(Member $member, int $assignmentId): array
    {
        $row = ServiceScheduleVolunteer::query()
            ->with(['volunteer', 'scheduleArea.schedule', 'scheduleArea.serviceArea'])
            ->find($assignmentId);

        if (! $row || (int) $row->volunteer?->member_id !== (int) $member->id) {
            throw new RuntimeException('Escala não encontrada.', 404);
        }

        $this->assertOpen($row->status, hasReject: false);
        $row->update(['status' => 'confirmado']);
        $row->refresh();

        return $this->mapService($row);
    }

    /**
     * @return array<string, mixed>
     */
    public function confirmMonthly(Member $member, int $pivotId): array
    {
        $pivot = DB::table('monthly_culto_service_areas')->where('id', $pivotId)->first();
        if (! $pivot || ! $this->volunteerBelongsToMember((int) $pivot->volunteer_id, $member)) {
            throw new RuntimeException('Escala não encontrada.', 404);
        }

        $this->assertOpen((string) ($pivot->status ?? 'pendente'), hasReject: false);

        DB::table('monthly_culto_service_areas')->where('id', $pivotId)->update([
            'status' => 'confirmado',
            'updated_at' => now(),
        ]);

        $pivot = DB::table('monthly_culto_service_areas')->where('id', $pivotId)->first();

        return $this->mapMonthly($pivot);
    }

    /**
     * @return array<string, mixed>
     */
    public function confirmMoriah(Member $member, int $pivotId): array
    {
        return $this->updateMoriah($member, $pivotId, 'confirmado');
    }

    /**
     * @return array<string, mixed>
     */
    public function rejectMoriah(Member $member, int $pivotId): array
    {
        return $this->updateMoriah($member, $pivotId, 'recusado');
    }

    /**
     * @return array<string, mixed>
     */
    private function updateMoriah(Member $member, int $pivotId, string $status): array
    {
        $pivot = DB::table('moriah_schedule_members')->where('id', $pivotId)->first();
        if (! $pivot || (int) $pivot->member_id !== (int) $member->id) {
            throw new RuntimeException('Escala não encontrada.', 404);
        }

        $schedule = MoriahSchedule::query()->find($pivot->moriah_schedule_id);
        if ($schedule && ! $schedule->request_confirmation) {
            throw new RuntimeException('Esta escala não pede confirmação.', 422);
        }

        $this->assertOpen((string) ($pivot->status ?? 'pendente'), hasReject: true);

        DB::table('moriah_schedule_members')->where('id', $pivotId)->update([
            'status' => $status,
            'updated_at' => now(),
        ]);

        $pivot = DB::table('moriah_schedule_members')->where('id', $pivotId)->first();

        return $this->mapMoriah($pivot);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function moriahItems(Member $member, Carbon $from, ?Carbon $until): array
    {
        $pivots = DB::table('moriah_schedule_members')
            ->where('member_id', $member->id)
            ->get();

        $items = [];
        foreach ($pivots as $pivot) {
            $mapped = $this->mapMoriah($pivot);
            if ($mapped === null || ! $this->inRange($mapped['starts_at'], $from, $until)) {
                continue;
            }
            $items[] = $mapped;
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function serviceItems(Member $member, Carbon $from, ?Carbon $until): array
    {
        $volunteerIds = $this->activeVolunteerIds($member);
        if ($volunteerIds === []) {
            return [];
        }

        $rows = ServiceScheduleVolunteer::query()
            ->whereIn('volunteer_id', $volunteerIds)
            ->with(['scheduleArea.schedule', 'scheduleArea.serviceArea'])
            ->get();

        $items = [];
        foreach ($rows as $row) {
            $mapped = $this->mapService($row);
            if ($mapped === null || ! $this->inRange($mapped['starts_at'], $from, $until)) {
                continue;
            }
            $items[] = $mapped;
        }

        return $items;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function monthlyItems(Member $member, Carbon $from, ?Carbon $until): array
    {
        $volunteerIds = $this->activeVolunteerIds($member);
        if ($volunteerIds === []) {
            return [];
        }

        $pivots = DB::table('monthly_culto_service_areas')
            ->whereIn('volunteer_id', $volunteerIds)
            ->get();

        $items = [];
        foreach ($pivots as $pivot) {
            $mapped = $this->mapMonthly($pivot);
            if ($mapped === null || ! $this->inRange($mapped['starts_at'], $from, $until)) {
                continue;
            }
            $items[] = $mapped;
        }

        return $items;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function mapService(ServiceScheduleVolunteer $row): ?array
    {
        $schedule = $row->scheduleArea?->schedule;
        if (! $schedule || ! in_array($schedule->status, ['publicada', 'rascunho'], true)) {
            return null;
        }

        $startsAt = $this->combine($schedule->date, $schedule->start_time);
        $status = (string) $row->status;

        return [
            'id' => 'service:'.$row->id,
            'assignment_id' => $row->id,
            'type' => 'service',
            'title' => (string) $schedule->title,
            'area' => $row->scheduleArea?->serviceArea?->name,
            'location' => $schedule->location,
            'starts_at' => $startsAt,
            'status' => $status,
            'can_confirm' => $status === 'pendente',
            'can_reject' => false,
        ];
    }

    /**
     * @param  object{id: mixed, monthly_culto_schedule_id: mixed, service_area_id: mixed, status?: mixed}  $pivot
     * @return array<string, mixed>|null
     */
    private function mapMonthly(object $pivot): ?array
    {
        $schedule = MonthlyCultoSchedule::query()->with('event')->find($pivot->monthly_culto_schedule_id);
        if (! $schedule || ! $schedule->event || ! in_array($schedule->status, ['publicada', 'rascunho'], true)) {
            return null;
        }

        $area = ServiceArea::query()->find($pivot->service_area_id);
        $status = (string) ($pivot->status ?? 'pendente');

        return [
            'id' => 'monthly:'.$pivot->id,
            'assignment_id' => (int) $pivot->id,
            'type' => 'monthly',
            'title' => (string) ($schedule->event->title ?: 'Culto'),
            'area' => $area?->name,
            'location' => $schedule->event->location,
            'starts_at' => $schedule->event->start_date?->toIso8601String(),
            'status' => $status,
            'can_confirm' => $status === 'pendente',
            'can_reject' => false,
        ];
    }

    /**
     * @param  object{id: mixed, moriah_schedule_id: mixed, status?: mixed}  $pivot
     * @return array<string, mixed>|null
     */
    private function mapMoriah(object $pivot): ?array
    {
        $schedule = MoriahSchedule::query()->with('event')->find($pivot->moriah_schedule_id);
        if (! $schedule || ! in_array($schedule->status, ['publicada', 'rascunho'], true)) {
            return null;
        }

        $status = (string) ($pivot->status ?? 'pendente');
        $asks = (bool) $schedule->request_confirmation;

        return [
            'id' => 'moriah:'.$pivot->id,
            'assignment_id' => (int) $pivot->id,
            'type' => 'moriah',
            'title' => (string) ($schedule->title ?: 'Moriah'),
            'area' => 'Moriah',
            'location' => $schedule->event?->location,
            'starts_at' => $this->combine($schedule->date, $schedule->time),
            'status' => $status,
            'can_confirm' => $asks && $status === 'pendente',
            'can_reject' => $asks && $status === 'pendente',
        ];
    }

    private function combine(mixed $date, mixed $time): ?string
    {
        if (! $date) {
            return null;
        }

        $day = $date instanceof Carbon ? $date->toDateString() : Carbon::parse((string) $date)->toDateString();
        $clock = '00:00:00';
        if ($time) {
            $clock = $time instanceof Carbon
                ? $time->format('H:i:s')
                : Carbon::parse((string) $time)->format('H:i:s');
        }

        return Carbon::parse($day.' '.$clock, config('app.timezone'))->toIso8601String();
    }

    private function inRange(?string $startsAt, Carbon $from, ?Carbon $until): bool
    {
        if ($startsAt === null) {
            return false;
        }

        $at = Carbon::parse($startsAt);
        if ($at->lt($from)) {
            return false;
        }

        return $until === null || $at->lte($until);
    }

    /**
     * @return list<int>
     */
    private function activeVolunteerIds(Member $member): array
    {
        return Volunteer::query()
            ->where('member_id', $member->id)
            ->where('status', 'ativo')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    private function volunteerBelongsToMember(int $volunteerId, Member $member): bool
    {
        return Volunteer::query()
            ->where('id', $volunteerId)
            ->where('member_id', $member->id)
            ->exists();
    }

    private function assertOpen(string $status, bool $hasReject): void
    {
        if ($status === 'pendente') {
            return;
        }

        $message = $hasReject
            ? 'Esta escala já foi respondida.'
            : 'Esta escala já foi confirmada.';

        throw new RuntimeException($message, 422);
    }
}
