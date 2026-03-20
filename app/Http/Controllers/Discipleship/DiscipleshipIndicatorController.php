<?php

namespace App\Http\Controllers\Discipleship;

use App\Http\Controllers\Controller;
use App\Models\Discipleship\DiscipleshipIndicator;
use App\Models\Discipleship\DiscipleshipIndicatorValue;
use App\Models\Discipleship\DiscipleshipMember;
use Illuminate\Http\Request;

class DiscipleshipIndicatorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $indicators = DiscipleshipIndicator::orderBy('order')->orderBy('nome')->get();
        
        return view('discipleship.indicators.index', compact('indicators'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('discipleship.indicators.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'tipo' => 'required|in:espiritual,material',
            'ativo' => 'boolean',
            'order' => 'nullable|integer|min:0',
        ], [
            'nome.required' => 'O campo nome é obrigatório.',
            'tipo.required' => 'O tipo é obrigatório.',
        ]);

        $validated['ativo'] = $request->has('ativo') ? true : false;
        $validated['order'] = $validated['order'] ?? 0;

        DiscipleshipIndicator::create($validated);

        return redirect()->route('discipleship.indicators.index')
            ->with('success', 'Indicador criado com sucesso!');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DiscipleshipIndicator $indicator)
    {
        return view('discipleship.indicators.edit', compact('indicator'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DiscipleshipIndicator $indicator)
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'tipo' => 'required|in:espiritual,material',
            'ativo' => 'boolean',
            'order' => 'nullable|integer|min:0',
        ], [
            'nome.required' => 'O campo nome é obrigatório.',
            'tipo.required' => 'O tipo é obrigatório.',
        ]);

        $validated['ativo'] = $request->has('ativo') ? true : false;
        $validated['order'] = $validated['order'] ?? 0;

        $indicator->update($validated);

        return redirect()->route('discipleship.indicators.index')
            ->with('success', 'Indicador atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DiscipleshipIndicator $indicator)
    {
        $indicator->delete();

        return redirect()->route('discipleship.indicators.index')
            ->with('success', 'Indicador excluído com sucesso!');
    }

    /**
     * Registrar valor do indicador para um membro
     */
    public function storeValue(Request $request)
    {
        $validated = $request->validate([
            'indicator_id' => 'required|exists:discipleship_indicators,id',
            'discipleship_member_id' => 'required|exists:discipleship_members,id',
            'valor' => 'required|in:0,1,2,3,4,5',
            'observacao' => 'nullable|string',
            'data_registro' => 'required|date',
        ]);

        DiscipleshipIndicatorValue::create($validated);

        return back()->with('success', 'Valor do indicador registrado com sucesso!');
    }
}
