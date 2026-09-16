<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\Pgi;
use App\Services\Pgis\MeetingAttendanceService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MePgiController extends Controller
{
    use ResolvesApiMember;

    public function show(Request $request): JsonResponse
    {
        $member = $this->requireMember($request);
        if ($member instanceof JsonResponse) {
            return $member;
        }

        $pgi = $this->pgiFor($request, $member);
        if ($pgi instanceof JsonResponse) {
            return $pgi;
        }

        $pgi->load(['leader1:id,name', 'leader2:id,name', 'leaderTraining1:id,name', 'leaderTraining2:id,name']);

        return ApiResponse::success($this->pgiPayload($pgi, $member, $request->user()->can('manageMeetings', $pgi)));
    }

    public function meetings(Request $request): JsonResponse
    {
        $member = $this->requireMember($request);
        if ($member instanceof JsonResponse) {
            return $member;
        }

        $pgi = $this->pgiFor($request, $member);
        if ($pgi instanceof JsonResponse) {
            return $pgi;
        }

        $limit = min(50, max(1, (int) $request->query('limit', 20)));

        $meetings = Meeting::query()
            ->where('pgi_id', $pgi->id)
            ->orderByDesc('meeting_date')
            ->limit($limit)
            ->get()
            ->map(fn (Meeting $meeting) => $this->meetingPayload($meeting));

        return ApiResponse::success($meetings->all());
    }

    public function meeting(Request $request, Meeting $meeting): JsonResponse
    {
        $member = $this->requireMember($request);
        if ($member instanceof JsonResponse) {
            return $member;
        }

        $pgi = $this->pgiFor($request, $member);
        if ($pgi instanceof JsonResponse) {
            return $pgi;
        }

        if ((int) $meeting->pgi_id !== (int) $pgi->id) {
            return ApiResponse::error('Reunião não encontrada.', 404);
        }

        $meeting->load(['attendances.member:id,name']);

        $payload = $this->meetingPayload($meeting);
        $payload['attendances'] = $meeting->attendances->map(function ($row) {
            return [
                'type' => $row->type,
                'member_id' => $row->member_id,
                'name' => $row->member?->name ?? $row->visitor_name,
                'phone' => $row->visitor_phone,
            ];
        })->values()->all();

        return ApiResponse::success($payload);
    }

    public function storeMeeting(Request $request): JsonResponse
    {
        $guard = $this->leaderContext($request);
        if ($guard instanceof JsonResponse) {
            return $guard;
        }
        [$pgi] = $guard;

        $validated = $request->validate([
            'meeting_date' => 'required|date',
            'subject' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ], [
            'meeting_date.required' => 'A data da reunião é obrigatória.',
            'meeting_date.date' => 'A data da reunião deve ser uma data válida.',
        ]);

        $meeting = Meeting::query()->create([
            'pgi_id' => $pgi->id,
            'meeting_date' => $validated['meeting_date'],
            'subject' => $validated['subject'] ?? null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return ApiResponse::success($this->meetingPayload($meeting), status: 201);
    }

    public function attendanceForm(Request $request, Meeting $meeting): JsonResponse
    {
        $guard = $this->leaderMeeting($request, $meeting);
        if ($guard instanceof JsonResponse) {
            return $guard;
        }
        [$pgi, $meeting] = $guard;

        $meeting->load('attendances');
        $present = $meeting->presentMemberIds();

        $members = $pgi->members()
            ->orderBy('name')
            ->get(['id', 'name', 'phone'])
            ->map(fn (Member $item) => [
                'id' => $item->id,
                'name' => $item->name,
                'phone' => $item->phone,
                'present' => in_array($item->id, $present, true),
            ])
            ->values()
            ->all();

        $visitors = $meeting->attendances
            ->where('type', 'visitor')
            ->values()
            ->map(fn ($row) => [
                'name' => $row->visitor_name,
                'phone' => $row->visitor_phone,
            ])
            ->all();

        return ApiResponse::success([
            'meeting' => $this->meetingPayload($meeting),
            'members' => $members,
            'visitors' => $visitors,
            'notes' => $meeting->notes,
        ]);
    }

    public function storeAttendance(Request $request, Meeting $meeting): JsonResponse
    {
        $guard = $this->leaderMeeting($request, $meeting);
        if ($guard instanceof JsonResponse) {
            return $guard;
        }
        [$pgi, $meeting] = $guard;

        $validated = $request->validate([
            'participants' => 'nullable|array',
            'participants.*' => 'integer|exists:members,id',
            'visitors' => 'nullable|array',
            'visitors.*.name' => 'nullable|string|max:255',
            'visitors.*.phone' => 'nullable|string|max:30',
            'notes' => 'nullable|string',
        ]);

        $saved = app(MeetingAttendanceService::class)
            ->save($pgi, $meeting, $validated, $request->user());

        $payload = $this->meetingPayload($saved);
        $payload['attendances'] = $saved->attendances->map(function ($row) {
            return [
                'type' => $row->type,
                'member_id' => $row->member_id,
                'name' => $row->member?->name ?? $row->visitor_name,
                'phone' => $row->visitor_phone,
            ];
        })->values()->all();

        return ApiResponse::success($payload);
    }

    /**
     * @return array{0: Pgi}|JsonResponse
     */
    private function leaderContext(Request $request): array|JsonResponse
    {
        $member = $this->requireMember($request);
        if ($member instanceof JsonResponse) {
            return $member;
        }

        $pgi = $this->pgiFor($request, $member);
        if ($pgi instanceof JsonResponse) {
            return $pgi;
        }

        if (! $request->user()->can('manageMeetings', $pgi)) {
            return ApiResponse::error('Somente o líder do PGI pode fazer isso.', 403);
        }

        return [$pgi];
    }

    /**
     * @return array{0: Pgi, 1: Meeting}|JsonResponse
     */
    private function leaderMeeting(Request $request, Meeting $meeting): array|JsonResponse
    {
        $context = $this->leaderContext($request);
        if ($context instanceof JsonResponse) {
            return $context;
        }
        [$pgi] = $context;

        if ((int) $meeting->pgi_id !== (int) $pgi->id) {
            return ApiResponse::error('Reunião não encontrada.', 404);
        }

        return [$pgi, $meeting];
    }

    private function pgiFor(Request $request, $member): Pgi|JsonResponse
    {
        if (! $member->pgi_id) {
            return ApiResponse::error('Você não está vinculado a um PGI.', 404, [
                'code' => 'pgi_not_found',
            ]);
        }

        $pgi = Pgi::query()->find($member->pgi_id);
        if (! $pgi) {
            return ApiResponse::error('Você não está vinculado a um PGI.', 404, [
                'code' => 'pgi_not_found',
            ]);
        }

        if (! $request->user()->can('view', $pgi)) {
            return ApiResponse::error('Você não tem acesso a este PGI.', 403);
        }

        return $pgi;
    }

    /**
     * @return array<string, mixed>
     */
    private function pgiPayload(Pgi $pgi, Member $member, bool $canManageMeetings): array
    {
        $leaders = collect([
            $pgi->leader1,
            $pgi->leader2,
            $pgi->leaderTraining1,
            $pgi->leaderTraining2,
        ])->filter()->map(fn ($leader) => [
            'id' => $leader->id,
            'name' => $leader->name,
        ])->values()->all();

        return [
            'id' => $pgi->id,
            'name' => $pgi->name,
            'day_of_week' => $pgi->day_of_week,
            'time_schedule' => $pgi->time_schedule,
            'address' => $pgi->fullAddress(),
            'neighborhood' => $pgi->neighborhood,
            'leaders' => $leaders,
            'is_leader' => $pgi->isLeader($member),
            'can_manage_meetings' => $canManageMeetings,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function meetingPayload(Meeting $meeting): array
    {
        return [
            'id' => $meeting->id,
            'meeting_date' => $meeting->meeting_date?->toDateString(),
            'subject' => $meeting->subject,
            'notes' => $meeting->notes,
            'participants_count' => (int) $meeting->participants_count,
            'visitors_count' => (int) $meeting->visitors_count,
            'attendance_registered' => $meeting->attendance_registered_at !== null,
        ];
    }
}
