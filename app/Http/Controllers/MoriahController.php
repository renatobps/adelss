<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Member;
use App\Models\MoriahFunction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MoriahController extends Controller
{
    /**
     * Exibe a página do Ministério Moriah
     */
    public function ministerio()
    {
        // Buscar o departamento "Louvor" (case-insensitive)
        $louvorDepartment = Department::whereRaw('LOWER(name) LIKE ?', ['%louvor%'])
            ->first();

        // Buscar todos os membros do departamento Louvor
        $members = collect();
        $totalMembers = 0;
        $activeMembers = 0;

        // Buscar todas as funções do Moriah
        $functions = MoriahFunction::orderBy('order')->orderBy('name')->get();

        if ($louvorDepartment) {
            // Buscar todos os membros do departamento Louvor
            $members = $louvorDepartment->members()
                ->with(['role', 'moriahFunctions'])
                ->orderBy('name')
                ->get();
            
            $totalMembers = $members->count();
            $activeMembers = $members->where('status', 'ativo')->count();
        }

        // Buscar membros disponíveis para adicionar (que não estão no departamento Louvor)
        $availableMembers = collect();
        if ($louvorDepartment) {
            $memberIdsInDepartment = $louvorDepartment->members()->pluck('members.id')->toArray();
            $availableMembers = Member::whereNotIn('id', $memberIdsInDepartment)
                ->where('status', 'ativo')
                ->orderBy('name')
                ->get(['id', 'name', 'photo_url']);
        } else {
            $availableMembers = Member::where('status', 'ativo')
                ->orderBy('name')
                ->get(['id', 'name', 'photo_url']);
        }

        return view('moriah.ministerio.index', [
            'louvorDepartment' => $louvorDepartment,
            'members' => $members,
            'totalMembers' => $totalMembers,
            'activeMembers' => $activeMembers,
            'functions' => $functions,
            'availableMembers' => $availableMembers,
        ]);
    }

    /**
     * Obtém as funções de um membro
     */
    public function getMemberFunctions(Member $member)
    {
        $functions = $member->moriahFunctions->pluck('id')->toArray();
        
        return response()->json([
            'functions' => $functions
        ]);
    }

    /**
     * Atualiza as funções de um membro
     */
    public function updateMemberFunctions(Request $request, Member $member)
    {
        $validated = $request->validate([
            'functions' => 'nullable|array',
            'functions.*' => 'exists:moriah_functions,id',
        ]);

        $member->moriahFunctions()->sync($validated['functions'] ?? []);

        return response()->json([
            'success' => true,
            'message' => 'Funções atualizadas com sucesso!'
        ]);
    }

    /**
     * Adiciona um ou mais membros ao departamento Louvor (e consequentemente ao Moriah)
     */
    public function addMemberToMinistry(Request $request)
    {
        $validated = $request->validate([
            'member_ids' => 'required|array',
            'member_ids.*' => 'exists:members,id',
        ]);

        $louvorDepartment = Department::whereRaw('LOWER(name) LIKE ?', ['%louvor%'])->first();

        if (!$louvorDepartment) {
            return response()->json([
                'success' => false,
                'message' => 'Departamento Louvor não encontrado!'
            ], 404);
        }

        $memberIds = $validated['member_ids'];
        $alreadyInDepartment = $louvorDepartment->members()
            ->whereIn('members.id', $memberIds)
            ->pluck('members.id')
            ->toArray();

        $newMemberIds = array_diff($memberIds, $alreadyInDepartment);

        if (empty($newMemberIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Todos os membros selecionados já estão no ministério!'
            ], 400);
        }

        $louvorDepartment->members()->attach($newMemberIds);

        $message = count($newMemberIds) === 1 
            ? 'Membro adicionado ao ministério com sucesso!'
            : count($newMemberIds) . ' membros adicionados ao ministério com sucesso!';

        if (count($alreadyInDepartment) > 0) {
            $message .= ' (' . count($alreadyInDepartment) . ' membro(s) já estavam no ministério)';
        }

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }

    /**
     * Remove um membro do departamento Louvor (e consequentemente do Moriah)
     */
    public function removeMemberFromMinistry(Member $member)
    {
        $louvorDepartment = Department::whereRaw('LOWER(name) LIKE ?', ['%louvor%'])->first();

        if (!$louvorDepartment) {
            return response()->json([
                'success' => false,
                'message' => 'Departamento Louvor não encontrado!'
            ], 404);
        }

        $louvorDepartment->members()->detach($member->id);

        return response()->json([
            'success' => true,
            'message' => 'Membro removido do ministério com sucesso!'
        ]);
    }

    /**
     * Atualiza o banner do ministério
     */
    public function updateBanner(Request $request)
    {
        $validated = $request->validate([
            'banner' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
        ], [
            'banner.required' => 'Selecione uma imagem para o banner.',
            'banner.image' => 'O banner deve ser uma imagem.',
            'banner.mimes' => 'O banner deve ser nos formatos: jpeg, png, jpg, gif, svg ou webp.',
            'banner.max' => 'O banner não pode ter mais de 5MB.',
        ]);

        $louvorDepartment = Department::whereRaw('LOWER(name) LIKE ?', ['%louvor%'])->first();

        if (!$louvorDepartment) {
            return response()->json([
                'success' => false,
                'message' => 'Departamento Louvor não encontrado!'
            ], 404);
        }

        // Remove banner antigo se existir
        if ($louvorDepartment->banner_url) {
            Storage::disk('public')->delete($louvorDepartment->banner_url);
        }

        // Upload do novo banner
        $bannerPath = $request->file('banner')->store('moriah/banners', 'public');
        $louvorDepartment->banner_url = $bannerPath;
        $louvorDepartment->save();

        return response()->json([
            'success' => true,
            'message' => 'Banner atualizado com sucesso!',
            'banner_url' => Storage::url($bannerPath)
        ]);
    }

    /**
     * Atualiza o logo do ministério
     */
    public function updateLogo(Request $request)
    {
        $validated = $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ], [
            'logo.required' => 'Selecione uma imagem para o logo.',
            'logo.image' => 'O logo deve ser uma imagem.',
            'logo.mimes' => 'O logo deve ser nos formatos: jpeg, png, jpg, gif, svg ou webp.',
            'logo.max' => 'O logo não pode ter mais de 2MB.',
        ]);

        $louvorDepartment = Department::whereRaw('LOWER(name) LIKE ?', ['%louvor%'])->first();

        if (!$louvorDepartment) {
            return response()->json([
                'success' => false,
                'message' => 'Departamento Louvor não encontrado!'
            ], 404);
        }

        // Remove logo antigo se existir
        if ($louvorDepartment->logo_url) {
            Storage::disk('public')->delete($louvorDepartment->logo_url);
        }

        // Upload do novo logo
        $logoPath = $request->file('logo')->store('moriah/logos', 'public');
        $louvorDepartment->logo_url = $logoPath;
        $louvorDepartment->save();

        return response()->json([
            'success' => true,
            'message' => 'Logo atualizado com sucesso!',
            'logo_url' => Storage::url($logoPath)
        ]);
    }
}
