<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DepartmentRole;
use App\Models\Member;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    /**
     * Templates pré-definidos para departamentos
     */
    private function getTemplates()
    {
        return [
            'louvor' => ['name' => 'Louvor', 'icon' => 'bx-music', 'color' => '#FF6B6B'],
            'midia' => ['name' => 'Mídia', 'icon' => 'bx-video-recording', 'color' => '#4ECDC4'],
            'diaconia' => ['name' => 'Diaconia', 'icon' => 'bx-heart', 'color' => '#FFE66D'],
            'ensino' => ['name' => 'Ensino', 'icon' => 'bx-book-open', 'color' => '#95E1D3'],
            'pastoral' => ['name' => 'Pastoral', 'icon' => 'bx-fire', 'color' => '#FF8C94'],
            'acolhimento' => ['name' => 'Acolhimento', 'icon' => 'bx-happy-heart-eyes', 'color' => '#A8E6CF'],
            'tesouraria' => ['name' => 'Tesouraria', 'icon' => 'bx-money', 'color' => '#FFD93D'],
            'missoes' => ['name' => 'Missões', 'icon' => 'bx-globe', 'color' => '#6BCB77'],
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'ativo'); // ativo ou arquivado
        
        $query = Department::with(['leader', 'leaders', 'members', 'roles']);
        
        if ($filter === 'arquivado') {
            $query->archived();
        } else {
            $query->active();
        }
        
        $departments = $query->orderBy('name')->paginate(15);
        
        return view('departments.index', compact('departments', 'filter'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $templates = $this->getTemplates();
        $selectedTemplate = $request->get('template');
        $members = Member::orderBy('name')->get();
        
        return view('departments.create', compact('templates', 'selectedTemplate', 'members'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Prepara o campo members antes da validação
        // Se members[] foi enviado como array, já vem como array do Laravel
        $membersData = $request->input('members', []);
        
        // Garante que seja array
        if (!is_array($membersData)) {
            $membersData = [];
        }
        
        // Filtra valores vazios
        $membersData = array_filter(array_map('intval', $membersData));
        
        // Substitui o valor no request
        $request->merge(['members' => $membersData]);

        $validated = $this->validate($request, [
            'name' => 'required|string|max:255',
            'template' => 'nullable|string',
            'icon' => 'nullable|string',
            'color' => 'nullable|string',
            'status' => 'required|in:ativo,arquivado',
            'description' => 'nullable|string',
            'leaders' => 'nullable|array',
            'leaders.*' => 'exists:members,id',
            'members' => 'sometimes|array',
            'members.*' => 'exists:members,id',
            'roles' => 'nullable|array',
            'roles.*.name' => 'required_without:roles.*.id|string|max:255',
            'roles.*.description' => 'nullable|string',
        ], [
            'name.required' => 'O campo nome do departamento é obrigatório.',
            'name.string' => 'O nome do departamento deve ser um texto.',
            'name.max' => 'O nome do departamento não pode ter mais de 255 caracteres.',
            'status.required' => 'O campo status é obrigatório.',
            'status.in' => 'O status deve ser "ativo" ou "arquivado".',
            'leaders.array' => 'Os líderes devem ser uma lista válida.',
            'leaders.*.exists' => 'Um ou mais líderes selecionados não existem.',
            'members.array' => 'Os participantes devem ser uma lista válida.',
            'members.*.exists' => 'Um ou mais participantes selecionados não existem.',
            'roles.*.name.required_without' => 'O nome do cargo é obrigatório.',
            'roles.*.name.string' => 'O nome do cargo deve ser um texto.',
            'roles.*.name.max' => 'O nome do cargo não pode ter mais de 255 caracteres.',
        ]);

        // Garante que members seja um array válido
        $validated['members'] = isset($validated['members']) && is_array($validated['members']) 
            ? array_filter($validated['members']) 
            : [];

        // Inicializa template se não foi enviado
        if (!isset($validated['template'])) {
            $validated['template'] = null;
        }

        $templates = $this->getTemplates();
        
        // Se foi selecionado um template, usar seus dados
        if (!empty($validated['template']) && isset($templates[$validated['template']])) {
            $template = $templates[$validated['template']];
            $validated['icon'] = $template['icon'] ?? $validated['icon'] ?? null;
            $validated['color'] = $template['color'] ?? $validated['color'] ?? null;
        }

        // Garante que icon e color existam
        if (!isset($validated['icon'])) {
            $validated['icon'] = null;
        }
        if (!isset($validated['color'])) {
            $validated['color'] = null;
        }

        $department = Department::create([
            'name' => $validated['name'],
            'template' => $validated['template'],
            'icon' => $validated['icon'],
            'color' => $validated['color'],
            'status' => $validated['status'],
            'description' => $validated['description'] ?? null,
        ]);

        // Sincronizar líderes
        if (!empty($validated['leaders']) && is_array($validated['leaders'])) {
            $department->leaders()->sync($validated['leaders']);
        }

        // Criar cargo padrão "Líder" se não existir
        if (!$department->roles()->where('is_default', true)->exists()) {
            DepartmentRole::create([
                'department_id' => $department->id,
                'name' => 'Líder',
                'description' => 'Líder do departamento',
                'is_default' => true,
            ]);
        }

        // Adicionar membros ao departamento
        if (!empty($validated['members']) && is_array($validated['members'])) {
            foreach ($validated['members'] as $memberId) {
                if (!empty($memberId) && is_numeric($memberId)) {
                    $department->members()->attach($memberId);
                }
            }
        }

        // Criar cargos/funções
        if (isset($validated['roles']) && is_array($validated['roles'])) {
            foreach ($validated['roles'] as $roleData) {
                DepartmentRole::create([
                    'department_id' => $department->id,
                    'name' => $roleData['name'],
                    'description' => $roleData['description'] ?? null,
                    'is_default' => false,
                ]);
            }
        }

        return redirect()->route('departments.index')
            ->with('success', 'Departamento cadastrado com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Department $department)
    {
        $department->load(['leader', 'leaders', 'members', 'roles']);
        // Carrega os roles para os membros através dos pivots
        $memberIds = $department->members->pluck('id')->toArray();
        $pivots = \App\Models\DepartmentMember::where('department_id', $department->id)
                                               ->whereIn('member_id', $memberIds)
                                               ->with('role')
                                               ->get()
                                               ->keyBy('member_id');
        return view('departments.show', compact('department', 'pivots'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Department $department)
    {
        $templates = $this->getTemplates();
        $members = Member::orderBy('name')->get();
        $department->load(['members', 'roles']);
        
        return view('departments.edit', compact('department', 'templates', 'members'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Department $department)
    {
        // Prepara o campo members antes da validação
        $membersData = $request->input('members', []);
        
        // Se members não foi enviado ou é string vazia, define como array vazio
        if ($membersData === null || $membersData === '') {
            $membersData = [];
        }
        
        // Se members for uma string JSON, converte para array
        if (is_string($membersData)) {
            if ($membersData === '[]' || $membersData === 'null' || trim($membersData) === '') {
                $membersData = [];
            } else {
                $decoded = json_decode($membersData, true);
                $membersData = is_array($decoded) ? array_filter($decoded) : [];
            }
        } elseif (!is_array($membersData)) {
            $membersData = [];
        } else {
            $membersData = array_filter($membersData, function($item) {
                return !empty($item);
            });
        }
        
        // Prepara roles_to_delete
        $rolesToDelete = $request->input('roles_to_delete', []);
        if (is_string($rolesToDelete)) {
            if ($rolesToDelete === '' || $rolesToDelete === '[]' || $rolesToDelete === 'null') {
                $rolesToDelete = [];
            } else {
                $decoded = json_decode($rolesToDelete, true);
                $rolesToDelete = is_array($decoded) ? array_filter($decoded) : [];
            }
        } elseif (!is_array($rolesToDelete)) {
            $rolesToDelete = [];
        } else {
            $rolesToDelete = array_filter($rolesToDelete);
        }
        
        // Substitui os valores no request - sempre como arrays
        $request->merge([
            'members' => $membersData,
            'roles_to_delete' => $rolesToDelete
        ]);

        $validated = $this->validate($request, [
            'name' => 'required|string|max:255',
            'template' => 'nullable|string',
            'icon' => 'nullable|string',
            'color' => 'nullable|string',
            'status' => 'required|in:ativo,arquivado',
            'description' => 'nullable|string',
            'leaders' => 'nullable|array',
            'leaders.*' => 'exists:members,id',
            'members' => 'sometimes|array',
            'members.*' => 'exists:members,id',
            'roles' => 'nullable|array',
            'roles.*.id' => 'nullable|exists:department_roles,id',
            'roles.*.name' => 'required_without:roles.*.id|string|max:255',
            'roles.*.description' => 'nullable|string',
            'roles_to_delete' => 'nullable|array',
            'roles_to_delete.*' => 'exists:department_roles,id',
        ], [
            'name.required' => 'O campo nome do departamento é obrigatório.',
            'name.string' => 'O nome do departamento deve ser um texto.',
            'name.max' => 'O nome do departamento não pode ter mais de 255 caracteres.',
            'status.required' => 'O campo status é obrigatório.',
            'status.in' => 'O status deve ser "ativo" ou "arquivado".',
            'leaders.array' => 'Os líderes devem ser uma lista válida.',
            'leaders.*.exists' => 'Um ou mais líderes selecionados não existem.',
            'members.array' => 'Os participantes devem ser uma lista válida.',
            'members.*.exists' => 'Um ou mais participantes selecionados não existem.',
            'roles.*.name.required_without' => 'O nome do cargo é obrigatório.',
            'roles.*.name.string' => 'O nome do cargo deve ser um texto.',
            'roles.*.name.max' => 'O nome do cargo não pode ter mais de 255 caracteres.',
            'roles.*.id.exists' => 'O cargo selecionado não existe.',
            'roles_to_delete.array' => 'Os cargos para exclusão devem ser uma lista válida.',
            'roles_to_delete.*.exists' => 'Um ou mais cargos selecionados para exclusão não existem.',
        ]);

        // Garante que seja arrays válidos
        $validated['members'] = isset($validated['members']) && is_array($validated['members']) 
            ? array_filter($validated['members']) 
            : [];
        
        $validated['roles_to_delete'] = isset($validated['roles_to_delete']) && is_array($validated['roles_to_delete']) 
            ? array_filter($validated['roles_to_delete']) 
            : [];

        // Inicializa template se não foi enviado
        if (!isset($validated['template'])) {
            $validated['template'] = $department->template;
        }

        $templates = $this->getTemplates();
        
        // Se foi selecionado um template, usar seus dados
        if (!empty($validated['template']) && isset($templates[$validated['template']])) {
            $template = $templates[$validated['template']];
            $validated['icon'] = $template['icon'] ?? ($validated['icon'] ?? $department->icon ?? null);
            $validated['color'] = $template['color'] ?? ($validated['color'] ?? $department->color ?? null);
        } else {
            // Mantém os valores existentes se não houver template
            $validated['icon'] = $validated['icon'] ?? $department->icon ?? null;
            $validated['color'] = $validated['color'] ?? $department->color ?? null;
        }

        $department->update([
            'name' => $validated['name'],
            'template' => $validated['template'],
            'icon' => $validated['icon'],
            'color' => $validated['color'],
            'status' => $validated['status'],
            'description' => $validated['description'] ?? null,
        ]);

        // Sincronizar líderes
        if (isset($validated['leaders'])) {
            $department->leaders()->sync($validated['leaders'] ?? []);
        }

        // Sincronizar membros
        if (isset($validated['members'])) {
            $department->members()->sync($validated['members']);
        }

        // Atualizar/criar/excluir cargos
        if (isset($validated['roles_to_delete'])) {
            DepartmentRole::whereIn('id', $validated['roles_to_delete'])
                          ->where('department_id', $department->id)
                          ->where('is_default', false)
                          ->delete();
        }

        if (isset($validated['roles']) && is_array($validated['roles'])) {
            foreach ($validated['roles'] as $key => $roleData) {
                if (isset($roleData['id']) && is_numeric($roleData['id'])) {
                    // Atualizar cargo existente
                    DepartmentRole::where('id', $roleData['id'])
                                  ->where('department_id', $department->id)
                                  ->where('is_default', false)
                                  ->update([
                                      'name' => $roleData['name'],
                                      'description' => $roleData['description'] ?? null,
                                  ]);
                } elseif (strpos($key, 'new-') === 0 || !isset($roleData['id'])) {
                    // Criar novo cargo
                    DepartmentRole::create([
                        'department_id' => $department->id,
                        'name' => $roleData['name'],
                        'description' => $roleData['description'] ?? null,
                        'is_default' => false,
                    ]);
                }
            }
        }

        return redirect()->route('departments.index')
            ->with('success', 'Departamento atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Department $department)
    {
        try {
            // Remove os relacionamentos antes de excluir
            $department->members()->detach();
            
            // Exclui os cargos do departamento
            if ($department->roles()->count() > 0) {
                $department->roles()->delete();
            }
            
            // Exclui o departamento (soft delete se estiver configurado)
            $department->delete();

            return redirect()->route('departments.index', ['filter' => 'ativo'])
                ->with('success', 'Departamento excluído com sucesso!');
        } catch (\Exception $e) {
            return redirect()->route('departments.index', ['filter' => 'ativo'])
                ->with('error', 'Erro ao excluir departamento. Por favor, tente novamente.');
        }
    }
}
