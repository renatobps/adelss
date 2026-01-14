<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\FinancialTransaction;
use App\Models\FinancialTransactionAttachment;
use App\Models\Member;
use App\Models\FinancialContact;
use App\Models\FinancialCategory;
use App\Models\FinancialAccount;
use App\Models\FinancialCostCenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = FinancialTransaction::with(['member', 'contact', 'category', 'account', 'costCenter'])
            ->orderBy('transaction_date', 'desc');

        // Filtros
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('account_id') && $request->account_id) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->has('cost_center_id') && $request->cost_center_id) {
            $query->where('cost_center_id', $request->cost_center_id);
        }

        // Filtro de período
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        
        $query->whereBetween('transaction_date', [$startDate, $endDate]);

        // Busca
        if ($request->has('search') && $request->search) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $perPage = $request->input('per_page', 100);
        $transactions = $query->paginate($perPage);

        // Dados para o gráfico mensal (por dia do mês)
        $start = Carbon::parse($startDate);
        $end = Carbon::parse($endDate);
        
        // Buscar todas as transações do período de uma vez
        $periodTransactions = FinancialTransaction::whereBetween('transaction_date', [$startDate, $endDate])->get();
        
        // Agrupar por data
        $chartData = [];
        $currentDate = $start->copy();
        
        while ($currentDate <= $end) {
            $dateStr = $currentDate->format('Y-m-d');
            
            // Filtrar transações do dia atual comparando as datas formatadas
            $dayTransactions = $periodTransactions->filter(function ($transaction) use ($dateStr) {
                return $transaction->transaction_date && $transaction->transaction_date->format('Y-m-d') === $dateStr;
            });
            
            // Calcular valores separadamente
            $receitasPagas = $dayTransactions->filter(function ($t) {
                return $t->type === 'receita' && $t->is_paid;
            })->sum('amount');
            
            $despesasPagas = $dayTransactions->filter(function ($t) {
                return $t->type === 'despesa' && $t->is_paid;
            })->sum('amount');
            
            $aReceber = $dayTransactions->filter(function ($t) {
                return $t->type === 'receita' && !$t->is_paid;
            })->sum('amount');
            
            $aPagar = $dayTransactions->filter(function ($t) {
                return $t->type === 'despesa' && !$t->is_paid;
            })->sum('amount');
            
            $chartData[] = [
                'date' => $currentDate->format('d/m/Y'),
                'day' => (int)$currentDate->format('d'),
                'receitas' => (float) $receitasPagas,
                'despesas' => (float) $despesasPagas,
                'a_receber' => (float) $aReceber,
                'a_pagar' => (float) $aPagar,
            ];
            
            $currentDate->addDay();
        }

        // Resumo do período
        $summary = [
            'total_received' => FinancialTransaction::receitas()
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->where('is_paid', true)
                ->sum('amount'),
            'total_paid' => FinancialTransaction::despesas()
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->where('is_paid', true)
                ->sum('amount'),
            'to_receive' => FinancialTransaction::receitas()
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->where('is_paid', false)
                ->sum('amount'),
            'to_pay' => FinancialTransaction::despesas()
                ->whereBetween('transaction_date', [$startDate, $endDate])
                ->where('is_paid', false)
                ->sum('amount'),
        ];

        // Dados para filtros
        $categories = FinancialCategory::orderBy('name')->get();
        $accounts = FinancialAccount::orderBy('name')->get();
        $costCenters = FinancialCostCenter::orderBy('name')->get();
        $members = Member::orderBy('name')->get(); // Para o modal de receita
        $contacts = FinancialContact::orderBy('name')->get(); // Para o modal de despesa

        return view('financial.transactions.index', compact(
            'transactions',
            'chartData',
            'summary',
            'categories',
            'accounts',
            'costCenters',
            'members',
            'contacts',
            'startDate',
            'endDate'
        ));
    }

    /**
     * Store a newly created resource (receita)
     */
    public function storeReceita(Request $request)
    {
        // Validação customizada para "Recebido de"
        $memberId = $request->input('member_id');
        $receivedFromOther = $request->input('received_from_other');
        
        // Preparar dados de validação
        $validationRules = [
            'transaction_date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'is_paid' => 'boolean',
            'category_id' => 'nullable|exists:financial_categories,id',
            'account_id' => 'nullable|exists:financial_accounts,id',
            'cost_center_id' => 'nullable|exists:financial_cost_centers,id',
            'payment_type' => 'nullable|in:unico,parcelado',
            'document_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'competence_date' => 'nullable|date',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240', // 10MB
        ];

        $validationMessages = [
            'transaction_date.required' => 'A data é obrigatória.',
            'description.required' => 'A descrição é obrigatória.',
            'amount.required' => 'O valor é obrigatório.',
            'amount.min' => 'O valor deve ser maior que zero.',
            'attachments.max' => 'Máximo de 5 arquivos permitidos.',
            'attachments.*.max' => 'Cada arquivo não pode ter mais de 10MB.',
        ];

        // Validar campo "Recebido de"
        if ($memberId === 'other') {
            $validationRules['received_from_other'] = 'required|string|max:255';
            $validationRules['member_id'] = 'nullable';
            $validationMessages['received_from_other.required'] = 'Informe de quem foi recebido quando selecionar "Outros".';
        } elseif ($memberId && is_numeric($memberId)) {
            $validationRules['member_id'] = 'required|exists:members,id';
            $validationRules['received_from_other'] = 'nullable|string|max:255';
            $validationMessages['member_id.required'] = 'Selecione um membro ou escolha "Outros".';
            $validationMessages['member_id.exists'] = 'O membro selecionado não existe.';
        } else {
            return redirect()->back()
                ->withErrors(['member_id' => 'Selecione um membro ou escolha "Outros".'])
                ->withInput();
        }

        $validated = $request->validate($validationRules, $validationMessages);

        // Determinar status e tipo
        $validated['status'] = $request->has('is_paid') && $request->is_paid ? 'recebido' : 'a_receber';
        $validated['type'] = 'receita';
        $validated['is_paid'] = $request->has('is_paid') && $request->is_paid;
        
        // Garantir que apenas um campo seja preenchido
        if ($memberId === 'other') {
            $validated['member_id'] = null;
        } else {
            $validated['received_from_other'] = null;
        }

        // Criar transação
        $transaction = FinancialTransaction::create($validated);

        // Upload de anexos
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('financial/transactions/attachments', 'public');
                FinancialTransactionAttachment::create([
                    'transaction_id' => $transaction->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        $message = $request->input('save_action') === 'new' 
            ? 'Receita criada com sucesso! Continuar adicionando?'
            : 'Receita criada com sucesso!';

        return redirect()->route('financial.transactions.index')
            ->with('success', $message);
    }

    /**
     * Store a newly created resource (despesa)
     */
    public function storeDespesa(Request $request)
    {
        $validated = $request->validate([
            'transaction_date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'is_paid' => 'boolean',
            'contact_id' => 'nullable|exists:financial_contacts,id',
            'category_id' => 'nullable|exists:financial_categories,id',
            'account_id' => 'nullable|exists:financial_accounts,id',
            'cost_center_id' => 'nullable|exists:financial_cost_centers,id',
            'payment_type' => 'nullable|in:unico,parcelado',
            'document_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'competence_date' => 'nullable|date',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240', // 10MB
        ], [
            'transaction_date.required' => 'A data é obrigatória.',
            'description.required' => 'A descrição é obrigatória.',
            'amount.required' => 'O valor é obrigatório.',
            'amount.min' => 'O valor deve ser maior que zero.',
            'attachments.max' => 'Máximo de 5 arquivos permitidos.',
            'attachments.*.max' => 'Cada arquivo não pode ter mais de 10MB.',
        ]);

        // Determinar status
        $validated['status'] = $validated['is_paid'] ?? false ? 'pago' : 'a_pagar';
        $validated['type'] = 'despesa';

        // Criar transação
        $transaction = FinancialTransaction::create($validated);

        // Upload de anexos
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('financial/transactions/attachments', 'public');
                FinancialTransactionAttachment::create([
                    'transaction_id' => $transaction->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        $message = $request->input('save_action') === 'new' 
            ? 'Despesa criada com sucesso! Continuar adicionando?'
            : 'Despesa criada com sucesso!';

        return redirect()->route('financial.transactions.index')
            ->with('success', $message);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(FinancialTransaction $transaction)
    {
        $transaction->load(['member', 'contact', 'category', 'account', 'costCenter', 'attachments']);
        
        // Formatar dados para JSON
        return response()->json([
            'id' => $transaction->id,
            'type' => $transaction->type,
            'transaction_date' => $transaction->transaction_date ? $transaction->transaction_date->format('Y-m-d') : null,
            'description' => $transaction->description,
            'amount' => $transaction->amount,
            'is_paid' => $transaction->is_paid,
            'member_id' => $transaction->member_id,
            'received_from_other' => $transaction->received_from_other,
            'contact_id' => $transaction->contact_id,
            'category_id' => $transaction->category_id,
            'account_id' => $transaction->account_id,
            'cost_center_id' => $transaction->cost_center_id,
            'payment_type' => $transaction->payment_type,
            'document_number' => $transaction->document_number,
            'competence_date' => $transaction->competence_date ? $transaction->competence_date->format('Y-m-d') : null,
            'notes' => $transaction->notes,
            'attachments' => $transaction->attachments->map(function($attachment) {
                return [
                    'id' => $attachment->id,
                    'file_name' => $attachment->file_name,
                    'file_path' => $attachment->file_path,
                ];
            }),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FinancialTransaction $transaction)
    {
        $rules = [
            'transaction_date' => 'required|date',
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'is_paid' => 'boolean',
            'category_id' => 'nullable|exists:financial_categories,id',
            'account_id' => 'nullable|exists:financial_accounts,id',
            'cost_center_id' => 'nullable|exists:financial_cost_centers,id',
            'payment_type' => 'nullable|in:unico,parcelado',
            'document_number' => 'nullable|string|max:50',
            'notes' => 'nullable|string',
            'competence_date' => 'nullable|date',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|max:10240', // 10MB
            'remove_attachments' => 'nullable|array',
            'remove_attachments.*' => 'exists:financial_transaction_attachments,id',
        ];

        if ($transaction->type === 'receita') {
            $rules['member_id'] = 'nullable|required_without:received_from_other|exists:members,id';
            $rules['received_from_other'] = 'nullable|required_without:member_id|string|max:255';
        } else {
            $rules['contact_id'] = 'nullable|exists:financial_contacts,id';
        }

        $validated = $request->validate($rules);

        // Determinar status
        $validated['status'] = $validated['is_paid'] ?? false 
            ? ($transaction->type === 'receita' ? 'recebido' : 'pago')
            : ($transaction->type === 'receita' ? 'a_receber' : 'a_pagar');

        // Garantir que apenas um campo seja preenchido para receita
        if ($transaction->type === 'receita') {
            if ($validated['member_id'] === 'other' || empty($validated['member_id'])) {
                $validated['member_id'] = null;
            } else {
                $validated['received_from_other'] = null;
            }
        }

        // Remover anexos marcados para exclusão
        if ($request->has('remove_attachments')) {
            foreach ($request->remove_attachments as $attachmentId) {
                $attachment = FinancialTransactionAttachment::find($attachmentId);
                if ($attachment && $attachment->transaction_id === $transaction->id) {
                    Storage::disk('public')->delete($attachment->file_path);
                    $attachment->delete();
                }
            }
        }

        // Upload de novos anexos
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('financial/transactions/attachments', 'public');
                FinancialTransactionAttachment::create([
                    'transaction_id' => $transaction->id,
                    'file_path' => $path,
                    'file_name' => $file->getClientOriginalName(),
                    'file_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        // Remover campos de arquivos do validated antes de atualizar
        unset($validated['attachments'], $validated['remove_attachments']);

        $transaction->update($validated);

        return redirect()->route('financial.transactions.index')
            ->with('success', 'Transação atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FinancialTransaction $transaction)
    {
        try {
            // Remover anexos
            foreach ($transaction->attachments as $attachment) {
                Storage::disk('public')->delete($attachment->file_path);
                $attachment->delete();
            }

            $transaction->delete();

            return redirect()->route('financial.transactions.index')
                ->with('success', 'Transação removida com sucesso!');
        } catch (\Exception $e) {
            return redirect()->route('financial.transactions.index')
                ->with('error', 'Erro ao remover transação. Por favor, tente novamente.');
        }
    }

    /**
     * Display receipt for printing
     */
    public function receipt(FinancialTransaction $transaction)
    {
        $transaction->load(['member', 'contact', 'category']);
        return view('financial.transactions.receipt', compact('transaction'));
    }

    /**
     * Duplicate a transaction
     */
    public function duplicate(FinancialTransaction $transaction)
    {
        $newTransaction = $transaction->replicate();
        $newTransaction->transaction_date = now();
        $newTransaction->is_paid = false;
        $newTransaction->status = $transaction->type === 'receita' ? 'a_receber' : 'a_pagar';
        $newTransaction->save();

        return redirect()->route('financial.transactions.index')
            ->with('success', 'Transação duplicada com sucesso!');
    }

    /**
     * Update transaction description (quick edit)
     */
    public function updateDescription(Request $request, FinancialTransaction $transaction)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
        ]);

        $transaction->update(['description' => $validated['description']]);

        return response()->json([
            'success' => true,
            'message' => 'Descrição atualizada com sucesso!'
        ]);
    }

    /**
     * Export transactions to CSV
     */
    public function export(Request $request)
    {
        $query = FinancialTransaction::with(['member', 'contact', 'category', 'account', 'costCenter'])
            ->orderBy('transaction_date', 'desc');

        // Aplicar mesmos filtros da index
        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('category_id') && $request->category_id) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->has('account_id') && $request->account_id) {
            $query->where('account_id', $request->account_id);
        }

        if ($request->has('cost_center_id') && $request->cost_center_id) {
            $query->where('cost_center_id', $request->cost_center_id);
        }

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        
        $query->whereBetween('transaction_date', [$startDate, $endDate]);

        if ($request->has('search') && $request->search) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        $transactions = $query->get();

        $filename = 'transacoes_' . now()->format('Y-m-d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0'
        ];

        $callback = function() use ($transactions) {
            $file = fopen('php://output', 'w');
            
            // Adicionar BOM para UTF-8 (para Excel reconhecer corretamente)
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Cabeçalhos
            fputcsv($file, [
                'Data',
                'Tipo',
                'Descrição',
                'Valor',
                'Status',
                'Recebido de / Pago à',
                'Categoria',
                'Conta',
                'Centro de Custo',
                'Doc nº',
                'Anotações'
            ], ';');

            // Dados
            foreach ($transactions as $transaction) {
                $sourceName = $transaction->type === 'receita' 
                    ? ($transaction->member ? $transaction->member->name : ($transaction->received_from_other ?? 'Outros'))
                    : ($transaction->contact ? $transaction->contact->name : '-');

                fputcsv($file, [
                    $transaction->transaction_date->format('d/m/Y'),
                    ucfirst($transaction->type),
                    $transaction->description,
                    number_format($transaction->amount, 2, ',', '.'),
                    ucfirst(str_replace('_', ' ', $transaction->status)),
                    $sourceName,
                    $transaction->category ? $transaction->category->name : '-',
                    $transaction->account ? $transaction->account->name : '-',
                    $transaction->costCenter ? $transaction->costCenter->name : '-',
                    $transaction->document_number ?? '-',
                    $transaction->notes ?? '-'
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Import transactions from CSV
     */
    public function import(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt|max:10240', // 10MB
        ], [
            'import_file.required' => 'Selecione um arquivo para importar.',
            'import_file.file' => 'O arquivo selecionado é inválido.',
            'import_file.mimes' => 'O arquivo deve ser no formato CSV.',
            'import_file.max' => 'O arquivo não pode ter mais de 10MB.',
        ]);

        try {
            $file = $request->file('import_file');
            $handle = fopen($file->getRealPath(), 'r');
            
            // Pular primeira linha (cabeçalhos)
            $header = fgetcsv($handle, 1000, ';');
            if (!$header) {
                return redirect()->route('financial.transactions.index')
                    ->with('error', 'Arquivo CSV inválido ou vazio.');
            }

            $imported = 0;
            $errors = [];

            while (($row = fgetcsv($handle, 1000, ';')) !== false) {
                if (count($row) < 4) continue; // Pular linhas incompletas

                try {
                    // Mapear dados do CSV (ajuste conforme o formato esperado)
                    $data = [
                        'transaction_date' => $this->parseDate($row[0] ?? null),
                        'type' => strtolower($row[1] ?? 'receita'),
                        'description' => $row[2] ?? '',
                        'amount' => $this->parseAmount($row[3] ?? 0),
                        'status' => isset($row[4]) ? strtolower(str_replace(' ', '_', $row[4])) : 'recebido',
                        'is_paid' => isset($row[4]) && in_array(strtolower($row[4]), ['recebido', 'pago']),
                    ];

                    // Validar dados básicos
                    if (empty($data['transaction_date']) || empty($data['description']) || $data['amount'] <= 0) {
                        $errors[] = "Linha ignorada: dados incompletos";
                        continue;
                    }

                    // Criar transação
                    FinancialTransaction::create($data);
                    $imported++;

                } catch (\Exception $e) {
                    $errors[] = "Erro na linha: " . $e->getMessage();
                }
            }

            fclose($handle);

            $message = "Importação concluída! {$imported} transação(ões) importada(s).";
            if (count($errors) > 0) {
                $message .= " " . count($errors) . " erro(s) encontrado(s).";
            }

            return redirect()->route('financial.transactions.index')
                ->with('success', $message)
                ->with('import_errors', $errors);

        } catch (\Exception $e) {
            return redirect()->route('financial.transactions.index')
                ->with('error', 'Erro ao importar arquivo: ' . $e->getMessage());
        }
    }

    /**
     * Helper para parsear data
     */
    private function parseDate($dateString)
    {
        if (empty($dateString)) return null;

        // Tentar vários formatos
        $formats = ['d/m/Y', 'Y-m-d', 'd-m-Y', 'Y/m/d'];
        foreach ($formats as $format) {
            try {
                $date = Carbon::createFromFormat($format, trim($dateString));
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Helper para parsear valor
     */
    private function parseAmount($amountString)
    {
        if (empty($amountString)) return 0;

        // Remover formatação brasileira (R$, pontos, etc)
        $amount = str_replace(['R$', ' ', '.'], '', $amountString);
        $amount = str_replace(',', '.', $amount);
        
        return (float) $amount;
    }
}
