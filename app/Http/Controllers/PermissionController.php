<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\Permission;
use App\Models\MemberRole;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    /**
     * Tela principal de gestão de permissões.
     * Permite selecionar um membro e configurar suas permissões.
     */
    public function index(Request $request)
    {
        $members = Member::orderBy('name')->get();
        $roles = MemberRole::active()->orderBy('name')->get();

        // Seleção por membro (usuário específico)
        $selectedMemberId = $request->get('member_id');
        $selectedMember = $selectedMemberId ? Member::with('user.permissions')->find($selectedMemberId) : null;
        $user = $selectedMember?->user;

        // Seleção por função/cargo
        $selectedRoleId = $request->get('role_id');
        $selectedRole = $selectedRoleId ? MemberRole::with('permissions')->find($selectedRoleId) : null;

        // Carregar permissões agrupadas por módulo e hierarquia
        $modules = Permission::whereNull('parent_id')
            ->with(['children' => function($query) {
                $query->with('children')->orderBy('name');
            }])
            ->orderBy('module')
            ->orderBy('name')
            ->get();

        $assignedPermissions = $user ? $user->permissions->pluck('id')->toArray() : [];
        $assignedRolePermissions = $selectedRole ? $selectedRole->permissions->pluck('id')->toArray() : [];

        return view('permissions.index', compact(
            'members',
            'roles',
            'selectedMember',
            'user',
            'selectedRole',
            'modules',
            'assignedPermissions',
            'assignedRolePermissions'
        ));
    }

    /**
     * Atualizar permissões de um membro.
     */
    public function update(Request $request, Member $member)
    {
        $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $user = $member->user;
        if (!$user) {
            return redirect()->route('permissions.index', ['member_id' => $member->id])
                ->with('error', 'Este membro ainda não possui usuário de acesso. Defina um e-mail para o membro e salve antes de configurar permissões.');
        }

        // Atualizar status de admin
        $user->is_admin = $request->has('is_admin') ? true : false;
        $user->save();

        // Se for admin, não precisa de permissões específicas
        if ($user->is_admin) {
            $user->permissions()->sync([]);
        } else {
            $permissions = $request->input('permissions', []);
            $user->permissions()->sync($permissions);
        }

        return redirect()->route('permissions.index', ['member_id' => $member->id])
            ->with('success', 'Permissões atualizadas com sucesso!');
    }

    /**
     * Atualizar permissões de uma função/cargo.
     */
    public function updateRole(Request $request, MemberRole $role)
    {
        $request->validate([
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $permissions = $request->input('permissions', []);
        $role->permissions()->sync($permissions);

        return redirect()->route('permissions.index', ['role_id' => $role->id])
            ->with('success', 'Permissões da função atualizadas com sucesso!');
    }
}

