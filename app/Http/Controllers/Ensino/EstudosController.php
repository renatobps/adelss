<?php

namespace App\Http\Controllers\Ensino;

use App\Http\Controllers\Controller;
use App\Models\Study;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class EstudosController extends Controller
{
    public function index(Request $request)
    {
        $query = Study::query();

        // Busca
        if ($request->filled('search')) {
            $query->search($request->search);
        }

        // Ordenação
        $sortField = $request->get('sort', 'created_at');
        $sortDirection = $request->get('direction', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Paginação
        $perPage = $request->get('per_page', 20);
        $studies = $query->paginate($perPage)->withQueryString();

        return view('ensino.estudos.index', compact('studies'));
    }

    public function create()
    {
        return view('ensino.estudos.create');
    }
    

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'nullable|string',
            'featured_image' => 'nullable|image|max:5120', // 5MB
            'attachment' => 'nullable|file|max:10240', // 10MB
            'send_notification' => 'boolean',
        ]);

        // Upload da imagem em destaque
        if ($request->hasFile('featured_image')) {
            $validated['featured_image'] = $request->file('featured_image')->store('studies/images', 'public');
        }

        // Upload do anexo
        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $validated['attachment'] = $file->store('studies/attachments', 'public');
            $validated['attachment_name'] = $file->getClientOriginalName();
        }

        $validated['send_notification'] = $request->has('send_notification');

        Study::create($validated);

        return redirect()->route('ensino.estudos.index')
            ->with('success', 'Estudo criado com sucesso!');
    }

    public function edit(Study $estudo)
    {
        return view('ensino.estudos.edit', compact('estudo'));
    }

    public function update(Request $request, Study $estudo)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'content' => 'nullable|string',
            'featured_image' => 'nullable|image|max:5120', // 5MB
            'attachment' => 'nullable|file|max:10240', // 10MB
            'send_notification' => 'boolean',
            'remove_featured_image' => 'boolean',
            'remove_attachment' => 'boolean',
        ]);

        // Remover imagem em destaque se solicitado
        if ($request->has('remove_featured_image') && $request->remove_featured_image == '1' && $estudo->featured_image) {
            Storage::disk('public')->delete($estudo->featured_image);
            $validated['featured_image'] = null;
        } else {
            unset($validated['remove_featured_image']);
        }

        // Upload da nova imagem em destaque
        if ($request->hasFile('featured_image')) {
            // Remove a imagem antiga
            if ($estudo->featured_image) {
                Storage::disk('public')->delete($estudo->featured_image);
            }
            $validated['featured_image'] = $request->file('featured_image')->store('studies/images', 'public');
        } else {
            unset($validated['featured_image']);
        }

        // Remover anexo se solicitado
        if ($request->has('remove_attachment') && $request->remove_attachment == '1' && $estudo->attachment) {
            Storage::disk('public')->delete($estudo->attachment);
            $validated['attachment'] = null;
            $validated['attachment_name'] = null;
        } else {
            unset($validated['remove_attachment']);
        }

        // Upload do novo anexo
        if ($request->hasFile('attachment')) {
            // Remove o anexo antigo
            if ($estudo->attachment) {
                Storage::disk('public')->delete($estudo->attachment);
            }
            $file = $request->file('attachment');
            $validated['attachment'] = $file->store('studies/attachments', 'public');
            $validated['attachment_name'] = $file->getClientOriginalName();
        } else {
            unset($validated['attachment']);
            unset($validated['attachment_name']);
        }

        $validated['send_notification'] = $request->has('send_notification');

        $estudo->update($validated);

        return redirect()->route('ensino.estudos.index')
            ->with('success', 'Estudo atualizado com sucesso!');
    }

    public function destroy(Study $estudo)
    {
        // Remove arquivos
        if ($estudo->featured_image) {
            Storage::disk('public')->delete($estudo->featured_image);
        }
        if ($estudo->attachment) {
            Storage::disk('public')->delete($estudo->attachment);
        }

        $estudo->delete();

        return redirect()->route('ensino.estudos.index')
            ->with('success', 'Estudo removido com sucesso!');
    }
}

