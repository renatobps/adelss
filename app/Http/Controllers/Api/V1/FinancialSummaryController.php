<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Policies\Eventos\CampaignPolicy;
use App\Services\Api\FinancialModuleService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FinancialSummaryController extends Controller
{
    use ResolvesFinancialApi;

    public function __construct(private FinancialModuleService $module) {}

    public function module(Request $request): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'financial.access-module')) {
            return $denied;
        }

        $caps = $this->module->capabilities($request->user());

        return ApiResponse::success([
            'capabilities' => $caps,
            'nav' => $this->module->nav($caps),
        ]);
    }

    public function show(Request $request): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'financial.view-summary')) {
            return $denied;
        }

        $period = (string) $request->query('period', '1month');
        $allowed = ['today', '7days', '1month', 'last_month', '3months'];
        if (! in_array($period, $allowed, true)) {
            $period = '1month';
        }

        return ApiResponse::success($this->module->summary($period));
    }

    public function options(Request $request): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'financial.access-module')) {
            return $denied;
        }

        $options = $this->module->options();
        $options['members'] = \App\Models\Member::query()
            ->orderBy('name')
            ->limit(80)
            ->get(['id', 'name'])
            ->map(fn ($row) => ['id' => $row->id, 'name' => $row->name])
            ->all();

        return ApiResponse::success($options);
    }

    public function reports(Request $request): JsonResponse
    {
        if ($denied = $this->guardFinancial($request, 'financial.view-reports')) {
            return $denied;
        }

        $start = (string) $request->query('start_date', now()->startOfMonth()->toDateString());
        $end = (string) $request->query('end_date', now()->endOfMonth()->toDateString());

        return ApiResponse::success($this->module->reports($start, $end));
    }

    public static function campaignAllowed(\App\Models\User $user, string $action = 'view'): bool
    {
        return app(CampaignPolicy::class)->action($user, $action);
    }
}
