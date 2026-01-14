<?php

namespace App\Http\Controllers;

use App\Models\MemberRole;
use Illuminate\Http\Request;

class MemberRoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $roles = MemberRole::with('members')->orderBy('name')->get();
        return view('member-roles.index', compact('roles'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return redirect()->route('member-roles.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:member_roles,name',
            'description' => 'nullable|string',
        ], [
            'name.required' => 'O campo nome do cargo é obrigatório.',
            'name.unique' => 'Já existe um cargo com este nome.',
            'name.max' => 'O nome do cargo não pode ter mais de 255 caracteres.',
        ]);

        MemberRole::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => true,
        ]);

        return redirect()->route('member-roles.index')
            ->with('success', 'Cargo criado com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(MemberRole $memberRole)
    {
        $memberRole->load('members');
        return view('member-roles.show', compact('memberRole'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(MemberRole $memberRole)
    {
        return view('member-roles.edit', compact('memberRole'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, MemberRole $memberRole)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:member_roles,name,' . $memberRole->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'O campo nome do cargo é obrigatório.',
            'name.unique' => 'Já existe um cargo com este nome.',
            'name.max' => 'O nome do cargo não pode ter mais de 255 caracteres.',
        ]);

        $validated['is_active'] = $request->has('is_active') ? true : false;

        $memberRole->update($validated);

        return redirect()->route('member-roles.index')
            ->with('success', 'Cargo atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(MemberRole $memberRole)
    {
        try {
            // Verifica se há membros com este cargo
            if ($memberRole->members()->count() > 0) {
                return redirect()->route('member-roles.index')
                    ->with('error', 'Não é possível excluir este cargo pois existem membros associados a ele.');
            }

            $memberRole->delete();

            return redirect()->route('member-roles.index')
                ->with('success', 'Cargo excluído com sucesso!');
        } catch (\Exception $e) {
            return redirect()->route('member-roles.index')
                ->with('error', 'Erro ao excluir cargo. Por favor, tente novamente.');
        }
    }
}


