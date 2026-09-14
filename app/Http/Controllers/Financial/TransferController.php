<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\FinancialAccount;
use App\Models\FinancialTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransferController extends Controller
{
    public function store(Request $request)
    {
        $this->authorize('update', new FinancialAccount());

        $validated = $request->validate([
            'from_account_id' => 'required|exists:financial_accounts,id|different:to_account_id',
            'to_account_id' => 'required|exists:financial_accounts,id',
            'transfer_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
        ], [
            'from_account_id.required' => 'Selecione a conta de origem.',
            'from_account_id.different' => 'A conta de origem e a de destino devem ser diferentes.',
            'to_account_id.required' => 'Selecione a conta de destino.',
            'transfer_date.required' => 'Informe a data da transferência.',
            'amount.required' => 'Informe o valor transferido.',
            'amount.min' => 'O valor deve ser maior que zero.',
        ]);

        $validated['created_by'] = Auth::id();
        $transfer = FinancialTransfer::create($validated);
        $transfer->load(['fromAccount', 'toAccount']);

        return redirect()->route('financial.accounts.index', ['status' => $request->input('status', 'ativas')])
            ->with('success', sprintf(
                'Transferência de %s registrada: %s → %s.',
                'R$ ' . number_format((float) $transfer->amount, 2, ',', '.'),
                $transfer->fromAccount->name,
                $transfer->toAccount->name
            ));
    }

    public function destroy(Request $request, FinancialTransfer $transfer)
    {
        $this->authorize('delete', $transfer->fromAccount);

        $transfer->delete();

        return redirect()->route('financial.accounts.index', ['status' => $request->input('status', 'ativas')])
            ->with('success', 'Transferência estornada com sucesso!');
    }
}
