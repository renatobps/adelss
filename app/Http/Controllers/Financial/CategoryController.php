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
        $this->authorize('viewAny', FinancialCategory::class);
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
        $this->authorize('create', FinancialCategory::class);
        return view('financial.categories.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', FinancialCategory::class);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'type' => 'required|in:receita,despesa',
            'sends_receipt' => 'nullable|boolean',
        ], [
            'name.required' => 'O campo nome da categoria é obrigatório.',
            'name.max' => 'O nome da categoria não pode ter mais de 255 caracteres.',
            'type.required' => 'O campo tipo é obrigatório.',
            'type.in' => 'O tipo deve ser Receitas ou Despesas.',
        ]);

        $validated['sends_receipt'] = $request->boolean('sends_receipt') && $validated['type'] === 'receita';

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
        $this->authorize('update', $category);
        return view('financial.categories.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FinancialCategory $category)
    {
        $this->authorize('update', $category);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'type' => 'required|in:receita,despesa',
            'sends_receipt' => 'nullable|boolean',
        ], [
            'name.required' => 'O campo nome da categoria é obrigatório.',
            'name.max' => 'O nome da categoria não pode ter mais de 255 caracteres.',
            'type.required' => 'O campo tipo é obrigatório.',
            'type.in' => 'O tipo deve ser Receitas ou Despesas.',
        ]);

        $validated['sends_receipt'] = $request->boolean('sends_receipt') && $validated['type'] === 'receita';

        $category->update($validated);

        return redirect()->route('financial.categories.index')
            ->with('success', 'Categoria atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FinancialCategory $category)
    {
        $this->authorize('delete', $category);
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
