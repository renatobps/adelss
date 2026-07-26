<?php

namespace App\Http\Controllers;

use App\Models\MemberCustomField;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MemberCustomFieldController extends Controller
{
    public function index()
    {
        $this->authorizeManage();
        $fields = MemberCustomField::query()->orderBy('sort_order')->orderBy('name')->get();

        return view('members.custom-fields.index', compact('fields'));
    }

    public function store(Request $request)
    {
        $this->authorizeManage();
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'type' => ['required', Rule::in(array_keys(MemberCustomField::TYPES))],
            'options' => 'nullable|string',
            'is_required' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:999',
        ]);

        $options = null;
        if ($validated['type'] === 'select') {
            $options = collect(preg_split('/[\r\n,]+/', (string) ($validated['options'] ?? '')))
                ->map(fn ($o) => trim($o))
                ->filter()
                ->values()
                ->all();
        }

        MemberCustomField::create([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'options' => $options,
            'is_required' => $request->boolean('is_required'),
            'is_active' => true,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        return back()->with('success', 'Campo personalizado criado.');
    }

    public function update(Request $request, MemberCustomField $customField)
    {
        $this->authorizeManage();
        $validated = $request->validate([
            'name' => 'required|string|max:120',
            'type' => ['required', Rule::in(array_keys(MemberCustomField::TYPES))],
            'options' => 'nullable|string',
            'is_required' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'sort_order' => 'nullable|integer|min:0|max:999',
        ]);

        $options = null;
        if ($validated['type'] === 'select') {
            $options = collect(preg_split('/[\r\n,]+/', (string) ($validated['options'] ?? '')))
                ->map(fn ($o) => trim($o))
                ->filter()
                ->values()
                ->all();
        }

        $customField->update([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'options' => $options,
            'is_required' => $request->boolean('is_required'),
            'is_active' => $request->boolean('is_active'),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        return back()->with('success', 'Campo atualizado.');
    }

    public function destroy(MemberCustomField $customField)
    {
        $this->authorizeManage();
        $customField->delete();

        return back()->with('success', 'Campo removido.');
    }

    private function authorizeManage(): void
    {
        $user = auth()->user();
        if ($user?->is_admin) {
            return;
        }
        if ($user?->hasPermission('members.index.manage') || $user?->hasPermission('members.index.edit')) {
            return;
        }
        abort(403);
    }
}
