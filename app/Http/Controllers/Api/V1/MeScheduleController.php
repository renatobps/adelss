<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Api\MemberScheduleService;
use App\Support\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class MeScheduleController extends Controller
{
    use ResolvesApiMember;

    public function __construct(private MemberScheduleService $schedules) {}

    public function index(Request $request): JsonResponse
    {
        $member = $this->requireMember($request);
        if ($member instanceof JsonResponse) {
            return $member;
        }

        $from = $this->parseDate($request->query('from'))?->startOfDay() ?? now()->startOfDay();
        $until = $this->parseDate($request->query('until'))?->endOfDay();

        $items = $this->schedules->upcoming($member, $from, $until);

        return ApiResponse::success($items, [
            'from' => $from->toDateString(),
            'until' => $until?->toDateString(),
            'pending' => collect($items)->where('status', 'pendente')->count(),
        ]);
    }

    public function confirmService(Request $request, int $assignment): JsonResponse
    {
        return $this->run($request, fn ($member) => $this->schedules->confirmService($member, $assignment));
    }

    public function confirmMonthly(Request $request, int $assignment): JsonResponse
    {
        return $this->run($request, fn ($member) => $this->schedules->confirmMonthly($member, $assignment));
    }

    public function confirmMoriah(Request $request, int $assignment): JsonResponse
    {
        return $this->run($request, fn ($member) => $this->schedules->confirmMoriah($member, $assignment));
    }

    public function rejectMoriah(Request $request, int $assignment): JsonResponse
    {
        return $this->run($request, fn ($member) => $this->schedules->rejectMoriah($member, $assignment));
    }

    /**
     * @param  callable(\App\Models\Member): array<string, mixed>  $action
     */
    private function run(Request $request, callable $action): JsonResponse
    {
        $member = $this->requireMember($request);
        if ($member instanceof JsonResponse) {
            return $member;
        }

        try {
            return ApiResponse::success($action($member));
        } catch (RuntimeException $e) {
            $status = $e->getCode();
            if (! in_array($status, [403, 404, 422], true)) {
                $status = 400;
            }

            return ApiResponse::error($e->getMessage(), $status);
        }
    }

    private function parseDate(mixed $value): ?Carbon
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }
}
