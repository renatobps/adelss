<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Pgi;
use App\Services\Api\MemberScheduleService;
use App\Services\Api\UserApiProfile;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MeHomeController extends Controller
{
    public function show(
        Request $request,
        UserApiProfile $profile,
        MemberScheduleService $schedules,
        AgendaEventController $agenda
    ): JsonResponse {
        $user = $request->user();
        $user->loadMissing('member');
        $member = $user->member;
        $payload = $profile->for($user);

        $nextSchedules = [];
        $pending = 0;
        if ($member) {
            $all = $schedules->upcoming($member, now()->startOfDay());
            $pending = collect($all)->where('status', 'pendente')->count();
            $nextSchedules = array_slice($all, 0, 5);
        }

        $events = Event::query()
            ->with('category:id,name,color')
            ->where('status', '!=', 'cancelado')
            ->where('start_date', '>=', now()->startOfDay())
            ->orderBy('start_date')
            ->limit(5)
            ->get()
            ->map(fn (Event $event) => $agenda->payload($event))
            ->all();

        $pgi = null;
        if ($member?->pgi_id) {
            $pgiModel = Pgi::query()->find($member->pgi_id);
            if ($pgiModel && $user->can('view', $pgiModel)) {
                $pgi = [
                    'id' => $pgiModel->id,
                    'name' => $pgiModel->name,
                    'is_leader' => $pgiModel->isLeader($member),
                    'can_manage_meetings' => $user->can('manageMeetings', $pgiModel),
                ];
            }
        }

        return ApiResponse::success([
            'profile' => $payload,
            'pending_schedules' => $pending,
            'next_schedules' => $nextSchedules,
            'next_events' => $events,
            'pgi' => $pgi,
            'can_view_financial' => $user->can('financial.view-summary'),
        ]);
    }
}
