<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DepartmentProject;
use App\Models\DepartmentProjectAction;
use Illuminate\Http\Request;

class DepartmentProjectActionController extends Controller
{
    public function create(Department $department, DepartmentProject $project)
    {
        $this->authorize('create', Department::class);
        $action = new DepartmentProjectAction;

        return view('departments.projects.action-form', compact('department', 'project', 'action'));
    }

    public function store(Request $request, Department $department, DepartmentProject $project)
    {
        $this->authorize('create', Department::class);
        $project->actions()->create($this->validatedAction($request));

        return redirect()->route('departments.projects.show', [$department, $project])->with('success', 'Ação cadastrada com sucesso!');
    }

    public function edit(Department $department, DepartmentProject $project, DepartmentProjectAction $action)
    {
        $this->authorize('update', $department);

        return view('departments.projects.action-form', compact('department', 'project', 'action'));
    }

    public function update(Request $request, Department $department, DepartmentProject $project, DepartmentProjectAction $action)
    {
        $this->authorize('update', $department);
        $action->update($this->validatedAction($request));

        return redirect()->route('departments.projects.show', [$department, $project])->with('success', 'Ação atualizada com sucesso!');
    }

    public function destroy(Department $department, DepartmentProject $project, DepartmentProjectAction $action)
    {
        $this->authorize('delete', $department);
        $action->delete();

        return redirect()->route('departments.projects.show', [$department, $project])->with('success', 'Ação excluída com sucesso!');
    }

    private function validatedAction(Request $request): array
    {
        return $request->validate(['name' => 'required|string|max:191', 'details' => 'nullable|string|max:10000']);
    }
}
