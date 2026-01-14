<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\FinancialCategory;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $receitas = FinancialCategory::receitas()->orderBy('name')->get();
        $despesas = FinancialCategory::despesas()->orderBy('name')->get();
        $total = FinancialCategory::count();
        
        return view('financial.categories.index', compact('receitas', 'despesas', 'total'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('financial.categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:receita,despesa',
        ], [
            'name.required' => 'O campo nome da categoria é obrigatório.',
            'name.max' => 'O nome da categoria não pode ter mais de 255 caracteres.',
            'type.required' => 'O campo tipo é obrigatório.',
            'type.in' => 'O tipo deve ser Receitas ou Despesas.',
        ]);

        FinancialCategory::create($validated);

        return redirect()->route('financial.categories.index')
            ->with('success', 'Categoria criada com sucesso!');
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
    public function edit(FinancialCategory $category)
    {
        return view('financial.categories.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FinancialCategory $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:receita,despesa',
        ], [
            'name.required' => 'O campo nome da categoria é obrigatório.',
            'name.max' => 'O nome da categoria não pode ter mais de 255 caracteres.',
            'type.required' => 'O campo tipo é obrigatório.',
            'type.in' => 'O tipo deve ser Receitas ou Despesas.',
        ]);

        $category->update($validated);

        return redirect()->route('financial.categories.index')
            ->with('success', 'Categoria atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FinancialCategory $category)
    {
        try {
            $category->delete();
            return redirect()->route('financial.categories.index')
                ->with('success', 'Categoria removida com sucesso!');
        } catch (\Exception $e) {
            return redirect()->route('financial.categories.index')
                ->with('error', 'Erro ao remover categoria. Por favor, tente novamente.');
        }
    }
}
