<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DepartmentRole;
use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DepartmentAdministrationController extends Controller
{
    public function index(Request $request, Department $department)
    {
        $this->authorize('view', $department);
        $roles = $department->roles()->with(['responsibleMembers', 'responsibleMember', 'departmentMembers.member'])->orderBy('name')->get();
        $members = $request->user()->can('update', $department)
            ? Member::orderBy('name')->get(['id', 'name']) : collect();

        return view('departments.administration.index', compact('department', 'roles', 'members'));
    }

    public function store(Request $request, Department $department)
    {
        $this->authorize('update', $department);
        $data = $this->validatedRole($request);
        DB::transaction(function () use ($department, $data) {
            $role = $department->roles()->create(collect($data)->only(['name', 'description'])->all());
            $this->syncAssignments($department, $role, $data);
        });

        return redirect()->route('departments.administration.index', $department)->with('success', 'Cargo cadastrado com sucesso!');
    }

    public function update(Request $request, Department $department, DepartmentRole $role)
    {
        $this->authorize('update', $department);
        $data = $this->validatedRole($request);
        if ($role->is_default) {
            $data['name'] = $role->name;
        }
        DB::transaction(function () use ($department, $role, $data) {
            $role->update(collect($data)->only(['name', 'description'])->all());
            $this->syncAssignments($department, $role, $data);
        });

        return redirect()->route('departments.administration.index', $department)->with('success', 'Cargo e pessoas atribuídas atualizados com sucesso!');
    }

    public function destroy(Department $department, DepartmentRole $role)
    {
        $this->authorize('update', $department);
        if ($role->is_default) {
            return back()->with('error', 'O cargo padrão não pode ser excluído.');
        }
        // Keep participant links and previous assignments when a role is archived.
        $role->delete();

        return redirect()->route('departments.administration.index', $department)->with('success', 'Cargo excluído com sucesso! Os participantes foram preservados.');
    }

    private function validatedRole(Request $request): array
    {
        if ($request->boolean('responsibles_present')) {
            $request->merge(['responsible_member_ids' => $request->input('responsible_member_ids', [])]);
        }
        return $request->validate([
            'name' => 'required|string|max:191',
            'description' => 'nullable|string|max:10000',
            'responsible_member_id' => ['nullable', 'integer', Rule::exists('members', 'id')->whereNull('deleted_at')],
            'responsible_member_ids' => 'sometimes|array|max:2000',
            'responsible_member_ids.*' => ['required', 'integer', 'distinct', Rule::exists('members', 'id')->whereNull('deleted_at')],
        ], [
            'responsible_member_id.exists' => 'Selecione um membro cadastrado que não tenha sido excluído.',
            'responsible_member_ids.*.exists' => 'Selecione apenas membros cadastrados que não tenham sido excluídos.',
            'responsible_member_ids.*.distinct' => 'Uma pessoa não pode aparecer duas vezes no mesmo cargo.',
        ]);
    }

    private function syncAssignments(Department $department, DepartmentRole $role, array $data): void
    {
        $current = $role->responsibleMembers()->withTrashed()->pluck('members.id');
        $legacy = $role->departmentMembers()->where('department_id', $department->id)->pluck('member_id');
        $previousPrimary = $role->responsible_member_id;
        if (array_key_exists('responsible_member_ids', $data)) {
            $selected = collect($data['responsible_member_ids'])->map(fn ($id) => (int) $id)->unique()->values();
            $knownIds = $current->merge($legacy)->push($previousPrimary)->filter()->unique();
            // Existing links to deleted members remain historical; they are not new selections.
            $unavailableIds = Member::withTrashed()->whereIn('id', $knownIds)->whereNotNull('deleted_at')->pluck('id');
            $ids = $selected->merge($unavailableIds)->unique()->values();
            $primary = $selected->contains((int) $previousPrimary) ? $previousPrimary : $selected->first();
            DB::table('department_members')->where('department_id', $department->id)->where('department_role_id', $role->id)
                ->whereNotIn('member_id', $ids)->update(['department_role_id' => null, 'updated_at' => now()]);
        } else {
            // Accept older clients that still submit the former single-person field.
            $primary = array_key_exists('responsible_member_id', $data) ? $data['responsible_member_id'] : $previousPrimary;
            $ids = $current->reject(fn ($id) => array_key_exists('responsible_member_id', $data) && (int) $id === (int) $previousPrimary)
                ->merge($legacy)->push($primary)->filter()->unique()->values();
        }
        $validIds = Member::withTrashed()->whereIn('id', $ids)->pluck('id')->all();
        $role->update(['responsible_member_id' => $primary]);
        $role->responsibleMembers()->sync($validIds);
        $activeIds = Member::whereIn('id', $validIds)->pluck('id')->all();
        // Membership and any different pre-existing role on the old pivot stay intact.
        $department->members()->syncWithoutDetaching($activeIds);
    }
}
