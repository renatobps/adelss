<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\DepartmentProject;
use Illuminate\Http\Request;

class DepartmentProjectController extends Controller
{
    public function index(Department $department)
    {
        $this->authorize('view', $department);
        $projects = $department->projects()->withCount('actions')->orderBy('name')->paginate(12);

        return view('departments.projects.index', compact('department', 'projects'));
    }

    public function create(Department $department)
    {
        $this->authorize('create', Department::class);
        $project = new DepartmentProject;

        return view('departments.projects.form', compact('department', 'project'));
    }

    public function store(Request $request, Department $department)
    {
        $this->authorize('create', Department::class);
        $project = $department->projects()->create($this->validatedProject($request));

        return redirect()->route('departments.projects.show', [$department, $project])->with('success', 'Projeto cadastrado com sucesso!');
    }

    public function show(Department $department, DepartmentProject $project)
    {
        $this->authorize('view', $department);
        $actions = $project->actions()->orderBy('name')->paginate(15);

        return view('departments.projects.show', compact('department', 'project', 'actions'));
    }

    public function edit(Department $department, DepartmentProject $project)
    {
        $this->authorize('update', $department);

        return view('departments.projects.form', compact('department', 'project'));
    }

    public function update(Request $request, Department $department, DepartmentProject $project)
    {
        $this->authorize('update', $department);
        $project->update($this->validatedProject($request));

        return redirect()->route('departments.projects.show', [$department, $project])->with('success', 'Projeto atualizado com sucesso!');
    }

    public function destroy(Department $department, DepartmentProject $project)
    {
        $this->authorize('delete', $department);
        $project->delete();

        return redirect()->route('departments.projects.index', $department)->with('success', 'Projeto e suas ações excluídos com sucesso!');
    }

    private function validatedProject(Request $request): array
    {
        return $request->validate(['name' => 'required|string|max:191', 'description' => 'nullable|string|max:10000']);
    }
}
