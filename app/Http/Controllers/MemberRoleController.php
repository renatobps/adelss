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

    /**
     * Download do template CSV
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_cargos.csv"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // Adicionar BOM para UTF-8 (para Excel reconhecer corretamente)
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Cabeçalhos
            fputcsv($file, [
                'nome',
                'descricao',
                'ativo'
            ], ',');

            // Exemplos de dados
            fputcsv($file, [
                'Pastor',
                'Líder espiritual da igreja',
                'sim'
            ], ',');

            fputcsv($file, [
                'Diácono',
                'Auxiliar nas atividades da igreja',
                'sim'
            ], ',');

            fputcsv($file, [
                'Secretário',
                'Responsável pela documentação',
                'sim'
            ], ',');

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Importar cargos de arquivo CSV
     */
    public function import(Request $request)
    {
        $request->validate([
            'import_file' => 'required|file|mimes:csv,txt|max:10240', // 10MB
        ], [
            'import_file.required' => 'Selecione um arquivo para importar.',
            'import_file.file' => 'O arquivo selecionado é inválido.',
            'import_file.mimes' => 'O arquivo deve ser no formato CSV.',
            'import_file.max' => 'O arquivo não pode ter mais de 10MB.',
        ]);

        try {
            $file = $request->file('import_file');
            $handle = fopen($file->getRealPath(), 'r');
            
            // Pular primeira linha (cabeçalhos)
            $header = fgetcsv($handle, 1000, ',');
            if (!$header) {
                return redirect()->route('member-roles.index')
                    ->with('error', 'Arquivo CSV inválido ou vazio.');
            }

            $imported = 0;
            $errors = [];
            $lineNumber = 1;

            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $lineNumber++;
                
                try {
                    // Mapear dados do CSV
                    $nome = trim($row[0] ?? '');
                    $descricao = trim($row[1] ?? '');
                    $ativo = strtolower(trim($row[2] ?? 'sim'));

                    // Validações obrigatórias
                    if (empty($nome)) {
                        $errors[] = "Linha {$lineNumber}: Nome é obrigatório";
                        continue;
                    }

                    // Validar se nome já existe
                    $existingRole = MemberRole::where('name', $nome)->first();
                    if ($existingRole) {
                        $errors[] = "Linha {$lineNumber}: Cargo já existe ({$nome})";
                        continue;
                    }

                    // Validar campo ativo
                    $isActive = in_array($ativo, ['sim', 's', 'yes', 'y', '1', 'true']) ? true : false;

                    // Preparar dados para criação
                    $data = [
                        'name' => $nome,
                        'description' => !empty($descricao) ? $descricao : null,
                        'is_active' => $isActive,
                    ];

                    // Criar cargo
                    MemberRole::create($data);
                    $imported++;

                } catch (\Exception $e) {
                    $errors[] = "Linha {$lineNumber}: " . $e->getMessage();
                }
            }

            fclose($handle);

            $message = "Importação concluída! {$imported} cargo(s) importado(s).";
            if (count($errors) > 0) {
                $message .= " " . count($errors) . " erro(s) encontrado(s).";
            }

            return redirect()->route('member-roles.index')
                ->with('success', $message)
                ->with('import_errors', $errors);

        } catch (\Exception $e) {
            return redirect()->route('member-roles.index')
                ->with('error', 'Erro ao importar arquivo: ' . $e->getMessage());
        }
    }
}


