<?php

namespace App\Services\Pgis;

use App\Models\Meeting;
use App\Models\MeetingAttendance;
use App\Models\Pgi;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class MeetingAttendanceService
{
    /**
     * @param  array{participants?: array<int, mixed>, visitors?: array<int, array<string, mixed>>, notes?: string|null}  $payload
     */
    public function save(Pgi $pgi, Meeting $meeting, array $payload, User $user): Meeting
    {
        $memberIds = $pgi->members()
            ->whereIn('id', $payload['participants'] ?? [])
            ->pluck('id')
            ->all();

        DB::transaction(function () use ($meeting, $memberIds, $payload, $user) {
            $meeting->attendances()->delete();

            foreach ($memberIds as $memberId) {
                MeetingAttendance::query()->create([
                    'meeting_id' => $meeting->id,
                    'member_id' => $memberId,
                    'type' => 'participant',
                ]);
            }

            foreach ($payload['visitors'] ?? [] as $visitor) {
                if (! is_array($visitor)) {
                    continue;
                }
                $name = trim((string) ($visitor['name'] ?? ''));
                if ($name === '') {
                    continue;
                }

                MeetingAttendance::query()->create([
                    'meeting_id' => $meeting->id,
                    'visitor_name' => $name,
                    'visitor_phone' => trim((string) ($visitor['phone'] ?? '')) ?: null,
                    'type' => 'visitor',
                ]);
            }

            if (array_key_exists('notes', $payload)) {
                $meeting->notes = $payload['notes'];
            }

            $meeting->attendance_registered_at = now();
            $meeting->attendance_registered_by = $user->id;
            $meeting->save();
            $meeting->updateCounters();
        });

        return $meeting->fresh(['attendances.member']);
    }
}
