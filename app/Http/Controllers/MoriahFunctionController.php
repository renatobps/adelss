<?php

namespace App\Http\Controllers;

use App\Models\MoriahFunction;
use Illuminate\Http\Request;

class MoriahFunctionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $functions = MoriahFunction::orderBy('order')->orderBy('name')->get();
        return view('moriah.funcoes.index', compact('functions'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'order' => 'nullable|integer|min:0',
        ]);

        MoriahFunction::create($validated);

        return redirect()->route('moriah.funcoes.index')
            ->with('success', 'Função cadastrada com sucesso!');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MoriahFunction $funcao)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'icon' => 'nullable|string|max:255',
            'order' => 'nullable|integer|min:0',
        ]);

        $funcao->update($validated);

        return redirect()->route('moriah.funcoes.index')
            ->with('success', 'Função atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MoriahFunction $funcao)
    {
        $funcao->delete();

        return redirect()->route('moriah.funcoes.index')
            ->with('success', 'Função excluída com sucesso!');
    }
}
