<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\FinancialTransaction;
use Illuminate\Http\JsonResponse;

class CultoController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('createReceita', FinancialTransaction::class);

        return response()->json([
            'cultos' => Event::paraLancamentoFinanceiro()->map(fn (Event $culto) => [
                'id' => $culto->id,
                'label' => $culto->display_name,
                'date' => $culto->start_date?->format('Y-m-d'),
            ])->values(),
        ]);
    }
}
