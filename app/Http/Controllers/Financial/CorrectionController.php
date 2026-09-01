<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\FinancialCostCenter;
use App\Models\FinancialTransaction;
use App\Models\Member;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CorrectionController extends Controller
{
    use AppliesFinancialTransactionListing;

    public function index(Request $request): View
    {
        $this->authorize('viewAny', FinancialTransaction::class);

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        $query = FinancialTransaction::with(['member', 'contact', 'category']);
        $this->applyListingFilters($query, $request, $startDate, $endDate);
        $this->applyListingSort($query, $request);

        $perPage = $request->input('per_page', 100);
        $transactions = $query->paginate($perPage)->withQueryString();

        $categories = FinancialCategory::orderBy('name')->get();
        $categoriesReceitas = $categories->where('type', 'receita')->values();
        $categoriesDespesas = $categories->where('type', 'despesa')->values();
        $accounts = FinancialAccount::orderBy('name')->get();
        $costCenters = FinancialCostCenter::orderBy('name')->get();
        $members = Member::orderBy('name')->get(['id', 'name']);

        return view('financial.correction.index', compact(
            'transactions',
            'categoriesReceitas',
            'categoriesDespesas',
            'accounts',
            'costCenters',
            'members',
            'startDate',
            'endDate'
        ));
    }

    public function updateName(Request $request, FinancialTransaction $transaction): JsonResponse
    {
        $this->authorize('update', $transaction);

        $memberId = $request->input('member_id');

        if ($memberId === null || $memberId === '') {
            $transaction->update([
                'member_id' => null,
                'received_from_other' => null,
                'contact_id' => null,
            ]);

            return response()->json(['success' => true, 'name' => '']);
        }

        $request->validate([
            'member_id' => 'required|exists:members,id',
        ]);

        $transaction->update([
            'member_id' => (int) $memberId,
            'received_from_other' => null,
            'contact_id' => null,
        ]);
        $transaction->load('member');

        return response()->json([
            'success' => true,
            'name' => $transaction->member?->name ?? '',
        ]);
    }

    public function updateDescription(Request $request, FinancialTransaction $transaction): JsonResponse
    {
        $this->authorize('update', $transaction);

        $validated = $request->validate([
            'description' => 'required|string|max:255',
        ]);

        $transaction->update(['description' => $validated['description']]);

        return response()->json([
            'success' => true,
            'description' => $transaction->description,
        ]);
    }

    public function updateAmount(Request $request, FinancialTransaction $transaction): JsonResponse
    {
        $this->authorize('update', $transaction);

        $amount = $this->parseAmount((string) $request->input('amount', ''));

        if ($amount < 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Informe um valor maior que zero.',
            ], 422);
        }

        $transaction->update(['amount' => $amount]);

        return response()->json([
            'success' => true,
            'amount' => number_format((float) $transaction->amount, 2, ',', '.'),
        ]);
    }

    private function parseAmount(string $raw): float
    {
        $raw = trim(str_replace(['R$', '+', '-', ' '], '', $raw));
        $raw = str_replace("\u{00A0}", '', $raw);

        if (str_contains($raw, ',') && str_contains($raw, '.')) {
            $raw = str_replace('.', '', $raw);
            $raw = str_replace(',', '.', $raw);
        } elseif (str_contains($raw, ',')) {
            $raw = str_replace(',', '.', $raw);
        }

        return round((float) $raw, 2);
    }
}
