<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
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

        // Itens por página (padrão: 10, opções: 10, 50, 100)
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 50, 100]) ? $perPage : 10;

        $members = $query->paginate($perPage);
        $members->appends($request->except('page')); // Preservar filtros na paginação

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
            'email' => 'nullable|email|unique:members,email|unique:users,email',
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

        // Criar usuário de acesso se houver e-mail
        if (!empty($member->email)) {
            User::updateOrCreate(
                ['member_id' => $member->id],
                [
                    'name' => $member->name,
                    'email' => $member->email,
                    // Senha padrão inicial para novos membros
                    'password' => Hash::make('123456'),
                    'is_admin' => false,
                ]
            );
        }

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
        $member->load(['department', 'departments', 'pgi', 'role', 'turmas.school']);
        
        // Carregar turmas onde o membro é aluno
        $memberTurmas = $member->turmas()->with('school')->get();
        
        // Carregar turmas onde o membro é professor (através de disciplinas)
        $teacherTurmas = \App\Models\Turma::whereHas('disciplines', function($disciplineQuery) use ($member) {
            $disciplineQuery->whereHas('teachers', function($teacherQuery) use ($member) {
                $teacherQuery->where('discipline_teachers.member_id', $member->id);
            });
        })->with('school')->get();
        
        // Combinar e remover duplicatas
        $allTurmas = $memberTurmas->merge($teacherTurmas)->unique('id');
        
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
        
        // Buscar voluntário do membro e suas escalas futuras
        $volunteer = \App\Models\Volunteer::where('member_id', $member->id)->where('status', 'ativo')->first();
        $upcomingSchedules = collect();
        $upcomingMonthlySchedules = collect();
        $moriahSchedules = collect();
        
        // Buscar escalas do Moriah onde o membro está escalado (independente de ser voluntário)
        $moriahSchedulePivots = \DB::table('moriah_schedule_members')
            ->where('member_id', $member->id)
            ->get();
        
        foreach ($moriahSchedulePivots as $pivot) {
            $moriahSchedule = \App\Models\MoriahSchedule::with(['event'])
                ->where('id', $pivot->moriah_schedule_id)
                ->first();
            
            if (!$moriahSchedule) continue;
            
            // Filtrar apenas escalas futuras e publicadas/rascunho
            $scheduleDate = is_string($moriahSchedule->date) ? \Carbon\Carbon::parse($moriahSchedule->date) : $moriahSchedule->date;
            if ($scheduleDate && 
                $scheduleDate->format('Y-m-d') >= now()->toDateString() && 
                in_array($moriahSchedule->status, ['publicada', 'rascunho'])) {
                
                $moriahSchedules->push([
                    'schedule' => $moriahSchedule,
                    'pivot' => $pivot,
                    'type' => 'moriah',
                ]);
            }
        }
        
        if ($volunteer) {
            // Buscar escalas futuras onde o voluntário está escalado (escalas normais)
            $scheduleVolunteers = \App\Models\ServiceScheduleVolunteer::where('volunteer_id', $volunteer->id)
                ->with([
                    'scheduleArea.schedule',
                    'scheduleArea.serviceArea'
                ])
                ->get();
            
            foreach ($scheduleVolunteers as $scheduleVolunteer) {
                $scheduleArea = $scheduleVolunteer->scheduleArea;
                if (!$scheduleArea) continue;
                
                $schedule = $scheduleArea->schedule;
                if (!$schedule) continue;
                
                // Filtrar apenas escalas futuras e publicadas/rascunho
                if ($schedule->date && $schedule->date >= now()->toDateString() && in_array($schedule->status, ['publicada', 'rascunho'])) {
                    $upcomingSchedules->push([
                        'schedule' => $schedule,
                        'scheduleArea' => $scheduleArea,
                        'serviceArea' => $scheduleArea->serviceArea,
                        'scheduleVolunteer' => $scheduleVolunteer,
                        'type' => 'normal',
                    ]);
                }
            }
            
            // Buscar escalas mensais onde o voluntário está escalado
            $monthlySchedulePivots = \DB::table('monthly_culto_service_areas')
                ->where('volunteer_id', $volunteer->id)
                ->get();
            
            foreach ($monthlySchedulePivots as $pivot) {
                $monthlySchedule = \App\Models\MonthlyCultoSchedule::with(['event'])
                    ->where('id', $pivot->monthly_culto_schedule_id)
                    ->first();
                
                if (!$monthlySchedule) continue;
                
                // Filtrar apenas escalas futuras e publicadas/rascunho
                if ($monthlySchedule->event && 
                    $monthlySchedule->event->start_date && 
                    $monthlySchedule->event->start_date >= now()->startOfDay() && 
                    in_array($monthlySchedule->status, ['publicada', 'rascunho'])) {
                    
                    $serviceArea = \App\Models\ServiceArea::find($pivot->service_area_id);
                    
                    $upcomingMonthlySchedules->push([
                        'schedule' => $monthlySchedule,
                        'serviceArea' => $serviceArea,
                        'pivot' => $pivot,
                        'type' => 'monthly',
                    ]);
                }
            }
            
            // Combinar e ordenar todas as escalas por data
            $allSchedules = $upcomingSchedules->merge($upcomingMonthlySchedules)->merge($moriahSchedules);
            $allSchedules = $allSchedules->sortBy(function($item) {
                if ($item['type'] === 'normal') {
                    $date = $item['schedule']->date->format('Y-m-d');
                    $time = $item['schedule']->start_time ? (is_object($item['schedule']->start_time) ? $item['schedule']->start_time->format('H:i') : \Carbon\Carbon::parse($item['schedule']->start_time)->format('H:i')) : '00:00';
                    return $date . ' ' . $time;
                } elseif ($item['type'] === 'monthly') {
                    $date = $item['schedule']->event->start_date->format('Y-m-d');
                    $time = $item['schedule']->event->start_date->format('H:i');
                    return $date . ' ' . $time;
                } else { // moriah
                    $date = $item['schedule']->date->format('Y-m-d');
                    $time = $item['schedule']->time ? (is_object($item['schedule']->time) ? $item['schedule']->time->format('H:i') : \Carbon\Carbon::parse($item['schedule']->time)->format('H:i')) : '00:00';
                    return $date . ' ' . $time;
                }
            })->values();
            
            // Contar escalas pendentes de confirmação
            $pendingSchedulesCount = $allSchedules->filter(function($item) {
                if ($item['type'] === 'normal') {
                    return $item['scheduleVolunteer']->status !== 'confirmado';
                } elseif ($item['type'] === 'monthly') {
                    return ($item['pivot']->status ?? 'pendente') !== 'confirmado';
                } else { // moriah
                    return ($item['pivot']->status ?? 'pendente') !== 'confirmado';
                }
            })->count();
            
            $upcomingSchedules = $allSchedules;
        } else {
            // Mesmo sem ser voluntário, pode ter escalas do Moriah
            if ($moriahSchedules->count() > 0) {
                $upcomingSchedules = $moriahSchedules->sortBy(function($item) {
                    $scheduleDate = is_string($item['schedule']->date) ? \Carbon\Carbon::parse($item['schedule']->date) : $item['schedule']->date;
                    $date = $scheduleDate ? $scheduleDate->format('Y-m-d') : '9999-12-31';
                    $time = $item['schedule']->time ? (is_object($item['schedule']->time) ? $item['schedule']->time->format('H:i') : \Carbon\Carbon::parse($item['schedule']->time)->format('H:i')) : '00:00';
                    return $date . ' ' . $time;
                })->values();
                
                $pendingSchedulesCount = $upcomingSchedules->filter(function($item) {
                    return ($item['pivot']->status ?? 'pendente') !== 'confirmado';
                })->count();
            } else {
                $upcomingSchedules = collect();
                $pendingSchedulesCount = 0;
            }
        }
        
        $tab = $request->get('tab', 'informacoes');
        
        // Carregar dados de permissões se estiver na aba de permissões
        $user = $member->user;
        $modules = null;
        $assignedPermissions = [];
        
        if ($tab === 'permissoes') {
            $modules = \App\Models\Permission::whereNull('parent_id')
                ->with(['children' => function($query) {
                    $query->with('children');
                }])
                ->orderBy('module')
                ->get();
            
            $assignedPermissions = $user ? $user->permissions->pluck('id')->toArray() : [];
        }
        
        return view('members.show', compact('member', 'departments', 'transactions', 'totalDizimo', 'totalOferta', 'volunteer', 'upcomingSchedules', 'pendingSchedulesCount', 'tab', 'user', 'modules', 'assignedPermissions', 'allTurmas', 'memberTurmas', 'teacherTurmas'));
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
        $user = auth()->user();
        $isAdmin = $user?->is_admin ?? false;
        $loggedMember = $user?->member;
        
        // Se não for admin, verificar se está editando o próprio perfil
        if (!$isAdmin && $loggedMember && $loggedMember->id !== $member->id) {
            // Verificar se tem permissão para editar outros membros
            if (!$user->hasPermission('members.index.edit') && 
                !$user->hasPermission('members.edit') &&
                !$user->hasPermission('members.index.manage')) {
                abort(403, 'Acesso negado. Você só pode editar o seu próprio perfil.');
            }
        }
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|unique:members,email,' . $member->id . '|unique:users,email,' . optional($member->user)->id,
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
            'new_password' => 'nullable|string|min:6|confirmed',
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

        // Garantir que o membro tenha usuário de acesso
        if (!empty($member->email)) {
            $user = $member->user ?: new User();
            $user->member_id = $member->id;
            $user->name = $member->name;
            $user->email = $member->email;

            if ($request->filled('new_password')) {
                $user->password = Hash::make($request->input('new_password'));
            } elseif (!$user->exists) {
                // Se ainda não existir usuário, define senha padrão
                $user->password = Hash::make('123456');
            }

            if ($user->is_admin === null) {
                $user->is_admin = false;
            }

            $user->save();
        }

        $tab = $request->get('tab', 'informacoes');
        return redirect()->route('members.show', ['member' => $member->id, 'tab' => $tab])
            ->with('success', 'Membro atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Member $member)
    {
        $user = auth()->user();
        $loggedMember = $user?->member;
        
        // Impedir que o membro exclua o próprio perfil
        if ($loggedMember && $loggedMember->id === $member->id) {
            abort(403, 'Você não pode excluir o seu próprio perfil.');
        }
        
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

