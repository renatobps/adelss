<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\FinancialAccount;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $accounts = FinancialAccount::orderBy('name')->get();
        $total = $accounts->count();
        
        return view('financial.accounts.index', compact('accounts', 'total'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('financial.accounts.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ], [
            'name.required' => 'O campo nome da conta é obrigatório.',
            'name.max' => 'O nome da conta não pode ter mais de 255 caracteres.',
        ]);

        FinancialAccount::create($validated);

        return redirect()->route('financial.accounts.index')
            ->with('success', 'Conta criada com sucesso!');
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
    public function edit(FinancialAccount $account)
    {
        return view('financial.accounts.edit', compact('account'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FinancialAccount $account)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ], [
            'name.required' => 'O campo nome da conta é obrigatório.',
            'name.max' => 'O nome da conta não pode ter mais de 255 caracteres.',
        ]);

        $account->update($validated);

        return redirect()->route('financial.accounts.index')
            ->with('success', 'Conta atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FinancialAccount $account)
    {
        try {
            $account->delete();
            return redirect()->route('financial.accounts.index')
                ->with('success', 'Conta removida com sucesso!');
        } catch (\Exception $e) {
            return redirect()->route('financial.accounts.index')
                ->with('error', 'Erro ao remover conta. Por favor, tente novamente.');
        }
    }
}
