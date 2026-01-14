<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\FinancialCostCenter;
use App\Models\Department;
use Illuminate\Http\Request;

class CostCenterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $costCenters = FinancialCostCenter::with('departments')->orderBy('name')->get();
        $departments = Department::orderBy('name')->get();
        $total = $costCenters->count();
        
        return view('financial.cost-centers.index', compact('costCenters', 'departments', 'total'));
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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'departments' => 'nullable|array',
            'departments.*' => 'exists:departments,id',
        ], [
            'name.required' => 'O campo nome do centro de custos é obrigatório.',
            'name.max' => 'O nome do centro de custos não pode ter mais de 255 caracteres.',
            'departments.array' => 'Os departamentos devem ser uma lista válida.',
            'departments.*.exists' => 'Um ou mais departamentos selecionados não existem.',
        ]);

        // Criar o centro de custo
        $costCenter = FinancialCostCenter::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        // Vincular departamentos se fornecidos
        if (isset($validated['departments']) && !empty($validated['departments'])) {
            $costCenter->departments()->sync($validated['departments']);
        }

        return redirect()->route('financial.cost-centers.index')
            ->with('success', 'Centro de custo criado com sucesso!');
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
    public function edit(FinancialCostCenter $costCenter)
    {
        return response()->json($costCenter->load('departments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FinancialCostCenter $costCenter)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'departments' => 'nullable|array',
            'departments.*' => 'exists:departments,id',
        ], [
            'name.required' => 'O campo nome do centro de custos é obrigatório.',
            'name.max' => 'O nome do centro de custos não pode ter mais de 255 caracteres.',
            'departments.array' => 'Os departamentos devem ser uma lista válida.',
            'departments.*.exists' => 'Um ou mais departamentos selecionados não existem.',
        ]);

        // Atualizar o centro de custo
        $costCenter->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        // Atualizar relacionamento com departamentos
        if (isset($validated['departments'])) {
            $costCenter->departments()->sync($validated['departments'] ?? []);
        }

        return redirect()->route('financial.cost-centers.index')
            ->with('success', 'Centro de custo atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(FinancialCostCenter $costCenter)
    {
        try {
            // Remover relacionamentos
            $costCenter->departments()->detach();
            $costCenter->delete();
            
            return redirect()->route('financial.cost-centers.index')
                ->with('success', 'Centro de custo removido com sucesso!');
        } catch (\Exception $e) {
            return redirect()->route('financial.cost-centers.index')
                ->with('error', 'Erro ao remover centro de custo. Por favor, tente novamente.');
        }
    }
}
