<?php

namespace App\Http\Controllers;

use App\Models\Member;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class MemberController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Member::with(['department', 'departments', 'pgi', 'role']);

        // Busca
        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        // Filtro por status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filtro por gênero
        if ($request->has('gender') && $request->gender) {
            $query->byGender($request->gender);
        }

        // Filtro por departamento
        if ($request->has('department_id') && $request->department_id) {
            $query->where('department_id', $request->department_id);
        }

        // Filtro por cargo
        if ($request->has('role_id') && $request->role_id) {
            $query->where('role_id', $request->role_id);
        }

        // Ordenação
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $members = $query->paginate(15);

        return view('members.index', compact('members'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $roles = \App\Models\MemberRole::active()->orderBy('name')->get();
        $departments = \App\Models\Department::active()->orderBy('name')->get();
        return view('members.create', compact('roles', 'departments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:members,email',
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|in:M,F',
            'marital_status' => 'nullable|in:solteiro,casado,divorciado,viuvo,uniao_estavel',
            'birth_date' => 'nullable|date',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:ativo,inativo,visitante,membro_transferido',
            'cpf' => 'nullable|string|unique:members,cpf',
            'rg' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:2',
            'zip_code' => 'nullable|string|max:10',
            'membership_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'departments' => 'nullable|array',
            'departments.*' => 'exists:departments,id',
            'pgi_id' => 'nullable|exists:pgis,id',
            'role_id' => 'nullable|exists:member_roles,id',
        ]);

        // Upload da foto
        if ($request->hasFile('photo')) {
            $path = $request->file('photo')->store('members/photos', 'public');
            $validated['photo_url'] = $path;
        }

        // Remover departments do validated para não tentar salvar diretamente
        $departments = $validated['departments'] ?? [];
        unset($validated['departments']);

        // Criar membro
        $member = Member::create($validated);

        // Sincronizar departamentos
        if (!empty($departments)) {
            $member->departments()->sync($departments);
        }

        return redirect()->route('members.index')
            ->with('success', 'Membro cadastrado com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Member $member, Request $request)
    {
        $member->load(['department', 'departments', 'pgi', 'role']);
        
        // Carregar departamentos para o formulário de edição
        $departments = \App\Models\Department::active()->orderBy('name')->get();
        
        // Carregar transações financeiras do membro (dízimo e oferta)
        $transactions = \App\Models\FinancialTransaction::where('member_id', $member->id)
            ->with(['category', 'account', 'attachments'])
            ->orderBy('transaction_date', 'desc')
            ->paginate(10, ['*'], 'page', $request->get('page', 1));
        
        // Estatísticas financeiras
        $totalDizimo = \App\Models\FinancialTransaction::where('member_id', $member->id)
            ->whereHas('category', function($q) {
                $q->where('name', 'like', '%Dizimo%')
                  ->orWhere('name', 'like', '%Dízimo%');
            })
            ->sum('amount');
        
        $totalOferta = \App\Models\FinancialTransaction::where('member_id', $member->id)
            ->whereHas('category', function($q) {
                $q->where('name', 'like', '%Oferta%');
            })
            ->sum('amount');
        
        $tab = $request->get('tab', 'informacoes');
        
        return view('members.show', compact('member', 'departments', 'transactions', 'totalDizimo', 'totalOferta', 'tab'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Member $member)
    {
        $roles = \App\Models\MemberRole::active()->orderBy('name')->get();
        $departments = \App\Models\Department::active()->orderBy('name')->get();
        return view('members.edit', compact('member', 'roles', 'departments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Member $member)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:members,email,' . $member->id,
            'phone' => 'nullable|string|max:20',
            'gender' => 'nullable|in:M,F',
            'marital_status' => 'nullable|in:solteiro,casado,divorciado,viuvo,uniao_estavel',
            'birth_date' => 'nullable|date',
            'photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status' => 'required|in:ativo,inativo,visitante,membro_transferido',
            'cpf' => 'nullable|string|unique:members,cpf,' . $member->id,
            'rg' => 'nullable|string|max:20',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:2',
            'zip_code' => 'nullable|string|max:10',
            'membership_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'departments' => 'nullable|array',
            'departments.*' => 'exists:departments,id',
            'pgi_id' => 'nullable|exists:pgis,id',
            'role_id' => 'nullable|exists:member_roles,id',
        ]);

        // Upload da foto
        if ($request->hasFile('photo')) {
            // Remove foto antiga se existir
            if ($member->photo_url) {
                Storage::disk('public')->delete($member->photo_url);
            }
            $path = $request->file('photo')->store('members/photos', 'public');
            $validated['photo_url'] = $path;
        }

        // Remover departments do validated para não tentar salvar diretamente
        $departments = $validated['departments'] ?? [];
        unset($validated['departments']);

        // Converter valores vazios para null (para limpar pgi, role se necessário)
        if (empty($validated['pgi_id'])) {
            $validated['pgi_id'] = null;
        }
        if (empty($validated['role_id'])) {
            $validated['role_id'] = null;
        }

        $member->update($validated);

        // Sincronizar departamentos
        $member->departments()->sync($departments ?? []);

        $tab = $request->get('tab', 'informacoes');
        return redirect()->route('members.show', ['member' => $member->id, 'tab' => $tab])
            ->with('success', 'Membro atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Member $member)
    {
        // Remove foto se existir
        if ($member->photo_url) {
            Storage::disk('public')->delete($member->photo_url);
        }

        $member->delete();

        return redirect()->route('members.index')
            ->with('success', 'Membro excluído com sucesso!');
    }

    /**
     * Mostrar tutorial de importação
     */
    public function importTutorial()
    {
        return view('members.import.tutorial');
    }

    /**
     * Download do template CSV
     */
    public function downloadTemplate()
    {
        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_membros.csv"',
        ];

        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // Adicionar BOM para UTF-8 (para Excel reconhecer corretamente)
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Cabeçalhos - Informações Básicas
            fputcsv($file, [
                'nome',
                'email',
                'telefone',
                'status',
                'genero',
                'estado_civil',
                'data_nascimento',
                'data_membresia',
                'cargo_id',
                'departamento_id'
            ], ',');

            // Exemplos de dados
            fputcsv($file, [
                'João Silva',
                'joao@email.com',
                '11999999999',
                'ativo',
                'M',
                'casado',
                '1990-05-15',
                '2020-01-10',
                '1',
                '1'
            ], ',');

            fputcsv($file, [
                'Maria Santos',
                'maria@email.com',
                '11988888888',
                'ativo',
                'F',
                'solteiro',
                '1995-08-20',
                '2021-03-05',
                '1',
                '2'
            ], ',');

            fputcsv($file, [
                'Pedro Oliveira',
                '',
                '11977777777',
                'visitante',
                'M',
                '',
                '',
                '',
                '2',
                ''
            ], ',');

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Importar membros de arquivo CSV
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
                return redirect()->route('members.index')
                    ->with('error', 'Arquivo CSV inválido ou vazio.');
            }

            $imported = 0;
            $errors = [];
            $lineNumber = 1;

            while (($row = fgetcsv($handle, 1000, ',')) !== false) {
                $lineNumber++;
                
                try {
                    // Mapear dados do CSV - Informações Básicas
                    $nome = trim($row[0] ?? '');
                    $email = trim($row[1] ?? '');
                    $telefone = trim($row[2] ?? '');
                    $status = strtolower(trim($row[3] ?? 'ativo'));
                    $genero = strtoupper(trim($row[4] ?? ''));
                    $estadoCivil = strtolower(trim($row[5] ?? ''));
                    $dataNascimento = trim($row[6] ?? '');
                    $dataMembresia = trim($row[7] ?? '');
                    $cargoId = trim($row[8] ?? '');
                    $departamentoId = trim($row[9] ?? '');

                    // Validações obrigatórias
                    if (empty($nome)) {
                        $errors[] = "Linha {$lineNumber}: Nome é obrigatório";
                        continue;
                    }

                    // Validar status (se fornecido)
                    $statusValidos = ['ativo', 'inativo', 'visitante', 'membro_transferido'];
                    if (!empty($status) && !in_array($status, $statusValidos)) {
                        $errors[] = "Linha {$lineNumber}: Status inválido. Use: ativo, inativo, visitante ou membro_transferido";
                        continue;
                    }

                    // Validar telefone se fornecido
                    $telefoneLimpo = null;
                    if (!empty($telefone)) {
                        $telefoneLimpo = preg_replace('/[^0-9]/', '', $telefone);
                        if (strlen($telefoneLimpo) < 10) {
                            $errors[] = "Linha {$lineNumber}: Telefone inválido (mínimo 10 dígitos)";
                            continue;
                        }

                        // Verificar se telefone já existe
                        $existingMember = Member::where('phone', $telefoneLimpo)->first();
                        if ($existingMember) {
                            $errors[] = "Linha {$lineNumber}: Telefone duplicado ({$telefoneLimpo})";
                            continue;
                        }
                    }

                    // Validar email se fornecido
                    if (!empty($email)) {
                        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            $errors[] = "Linha {$lineNumber}: Email inválido ({$email})";
                            continue;
                        }

                        // Verificar se email já existe
                        $existingEmail = Member::where('email', $email)->first();
                        if ($existingEmail) {
                            $errors[] = "Linha {$lineNumber}: Email duplicado ({$email})";
                            continue;
                        }
                    }

                    // Validar gênero se fornecido
                    if (!empty($genero) && !in_array($genero, ['M', 'F'])) {
                        $errors[] = "Linha {$lineNumber}: Gênero inválido. Use: M ou F";
                        continue;
                    }

                    // Validar estado civil se fornecido
                    $estadosCivisValidos = ['solteiro', 'casado', 'divorciado', 'viuvo', 'uniao_estavel'];
                    if (!empty($estadoCivil) && !in_array($estadoCivil, $estadosCivisValidos)) {
                        $errors[] = "Linha {$lineNumber}: Estado civil inválido. Use: solteiro, casado, divorciado, viuvo ou uniao_estavel";
                        continue;
                    }

                    // Validar e formatar data de nascimento
                    $birthDate = null;
                    if (!empty($dataNascimento)) {
                        try {
                            $birthDate = Carbon::createFromFormat('Y-m-d', $dataNascimento);
                        } catch (\Exception $e) {
                            try {
                                $birthDate = Carbon::createFromFormat('d/m/Y', $dataNascimento);
                            } catch (\Exception $e2) {
                                $errors[] = "Linha {$lineNumber}: Data de nascimento inválida. Use formato: YYYY-MM-DD ou DD/MM/YYYY";
                                continue;
                            }
                        }
                    }

                    // Validar e formatar data de membresia
                    $membershipDate = null;
                    if (!empty($dataMembresia)) {
                        try {
                            $membershipDate = Carbon::createFromFormat('Y-m-d', $dataMembresia);
                        } catch (\Exception $e) {
                            try {
                                $membershipDate = Carbon::createFromFormat('d/m/Y', $dataMembresia);
                            } catch (\Exception $e2) {
                                $errors[] = "Linha {$lineNumber}: Data de membresia inválida. Use formato: YYYY-MM-DD ou DD/MM/YYYY";
                                continue;
                            }
                        }
                    }

                    // Validar cargo_id se fornecido
                    if (!empty($cargoId)) {
                        $role = \App\Models\MemberRole::find($cargoId);
                        if (!$role) {
                            $errors[] = "Linha {$lineNumber}: Cargo inválido (ID: {$cargoId}). Verifique o ID na página de Cargos.";
                            continue;
                        }
                    }

                    // Validar departamentos se fornecido (pode ser múltiplos separados por vírgula)
                    $departamentosIds = [];
                    if (!empty($departamentoId)) {
                        // Separar por vírgula ou ponto e vírgula
                        $ids = preg_split('/[,;]/', $departamentoId);
                        foreach ($ids as $id) {
                            $id = trim($id);
                            if (!empty($id)) {
                                $department = \App\Models\Department::find($id);
                                if (!$department) {
                                    $errors[] = "Linha {$lineNumber}: Departamento inválido (ID: {$id}). Verifique o ID na página de Departamentos.";
                                    continue 2; // Continua para próxima linha
                                }
                                $departamentosIds[] = $id;
                            }
                        }
                    }

                    // Preparar dados para criação
                    $data = [
                        'name' => $nome,
                        'status' => !empty($status) ? $status : 'ativo',
                    ];

                    if (!empty($email)) {
                        $data['email'] = $email;
                    }

                    if ($telefoneLimpo) {
                        $data['phone'] = $telefoneLimpo;
                    }

                    if (!empty($genero)) {
                        $data['gender'] = $genero;
                    }

                    if (!empty($estadoCivil)) {
                        $data['marital_status'] = $estadoCivil;
                    }

                    if ($birthDate) {
                        $data['birth_date'] = $birthDate->format('Y-m-d');
                    }

                    if ($membershipDate) {
                        $data['membership_date'] = $membershipDate->format('Y-m-d');
                    }

                    if (!empty($cargoId)) {
                        $data['role_id'] = $cargoId;
                    }

                    // Criar membro
                    $member = Member::create($data);

                    // Sincronizar departamentos se houver
                    if (!empty($departamentosIds)) {
                        $member->departments()->sync($departamentosIds);
                    }

                    $imported++;

                } catch (\Exception $e) {
                    $errors[] = "Linha {$lineNumber}: " . $e->getMessage();
                }
            }

            fclose($handle);

            $message = "Importação concluída! {$imported} membro(s) importado(s).";
            if (count($errors) > 0) {
                $message .= " " . count($errors) . " erro(s) encontrado(s).";
            }

            return redirect()->route('members.index')
                ->with('success', $message)
                ->with('import_errors', $errors);

        } catch (\Exception $e) {
            return redirect()->route('members.index')
                ->with('error', 'Erro ao importar arquivo: ' . $e->getMessage());
        }
    }
}

