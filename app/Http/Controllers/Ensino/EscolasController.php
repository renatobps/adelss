<?php

namespace App\Http\Controllers\Ensino;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Member;
use Illuminate\Http\Request;

class EscolasController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = School::with('manager');

        // Busca
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Ordenação
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        $schools = $query->paginate(20)->withQueryString();

        // Lista de membros para o formulário
        $members = Member::orderBy('name')->get();

        return view('ensino.escolas.index', compact('schools', 'members'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $members = Member::orderBy('name')->get();
        return view('ensino.escolas.create', compact('members'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'manager_id' => 'nullable|exists:members,id',
        ]);

        School::create($validated);

        return redirect()->route('ensino.escolas.index')
            ->with('success', 'Escola criada com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(School $escola)
    {
        $escola->load(['manager', 'turmas']);
        return view('ensino.escolas.show', compact('escola'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(School $escola)
    {
        $members = Member::orderBy('name')->get();
        return view('ensino.escolas.edit', compact('escola', 'members'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, School $escola)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'manager_id' => 'nullable|exists:members,id',
        ]);

        $escola->update($validated);

        return redirect()->route('ensino.escolas.index')
            ->with('success', 'Escola atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(School $escola)
    {
        $escola->delete();

        return redirect()->route('ensino.escolas.index')
            ->with('success', 'Escola removida com sucesso!');
    }
}

