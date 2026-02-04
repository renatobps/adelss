<?php

namespace App\Http\Controllers;

use App\Models\MoriahSchedule;
use App\Models\Event;
use App\Models\Member;
use App\Models\Song;
use App\Models\MoriahFunction;
use App\Models\MoriahUnavailability;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MoriahScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Atualizar automaticamente escalas que já passaram o dia para "concluido"
        $today = Carbon::today();
        $now = Carbon::now();
        
        MoriahSchedule::where('status', '!=', 'concluido')
            ->where('status', '!=', 'rascunho')
            ->where(function($query) use ($today, $now) {
                // Escalas com data anterior à hoje
                $query->where('date', '<', $today)
                      // Ou escalas de hoje que já passaram a hora
                      ->orWhere(function($q) use ($today, $now) {
                          $q->where('date', '=', $today)
                            ->where(function($subQuery) use ($now) {
                                // Se não tem hora definida, considerar como passada se já passou meia-noite
                                $subQuery->whereNull('time')
                                         ->orWhere('time', '<=', $now->format('H:i:s'));
                            });
                      });
            })
            ->update(['status' => 'concluido']);

        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);
        $status = $request->get('status');

        $query = MoriahSchedule::with(['event', 'members', 'songs'])
            ->whereYear('date', $year)
            ->whereMonth('date', $month);

        if ($status) {
            $query->where('status', $status);
        }

        $schedules = $query->orderBy('date')->orderBy('time')->get();

        return view('moriah.schedules.index', compact('schedules', 'month', 'year'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);

        // Buscar cultos do mês que ainda não passaram
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
        $now = Carbon::now();

        $cultos = Event::whereBetween('start_date', [$startDate, $endDate])
            ->where(function($query) {
                $query->where('title', 'like', '%culto%')
                      ->orWhere('title', 'like', '%Culto%')
                      ->orWhereHas('category', function($q) {
                          $q->where('name', 'like', '%culto%')
                            ->orWhere('name', 'like', '%Culto%');
                      });
            })
            ->where('start_date', '>', $now) // Apenas cultos que ainda não passaram
            ->orderBy('start_date')
            ->get();

        // Buscar apenas membros do Moriah (que têm funções do Moriah)
        $members = Member::where('status', 'ativo')
            ->whereHas('moriahFunctions')
            ->with('moriahFunctions')
            ->orderBy('name')
            ->get();

        // Buscar todas as músicas (para seleção)
        $songs = Song::orderBy('title')->get();

        // Buscar todas as funções do Moriah
        $functions = MoriahFunction::orderBy('order')->orderBy('name')->get();

        return view('moriah.schedules.create', compact('cultos', 'members', 'songs', 'functions', 'month', 'year'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'event_id' => 'nullable|exists:events,id',
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'time' => 'nullable',
            'observations' => 'nullable|string|max:500',
            'status' => 'required|in:rascunho,publicada,concluido',
            'request_confirmation' => 'boolean',
            'members' => 'nullable|array',
            'members.*' => 'exists:members,id',
            'songs' => 'nullable|array',
            'songs.*' => 'exists:songs,id',
        ]);

        $data = $request->only([
            'event_id', 'title', 'date', 'time', 'observations', 'status', 'request_confirmation'
        ]);

        // Verificar indisponibilidades antes de criar
        $unavailableMembers = [];
        if ($request->has('members') && !empty($request->members)) {
            $scheduleDate = Carbon::parse($request->date);
            
            foreach ($request->members as $memberId) {
                $unavailability = MoriahUnavailability::where('member_id', $memberId)
                    ->where(function($query) use ($scheduleDate) {
                        $query->where(function($q) use ($scheduleDate) {
                            $q->where('start_date', '<=', $scheduleDate)
                              ->where(function($query) use ($scheduleDate) {
                                  $query->whereNull('end_date')
                                        ->orWhere('end_date', '>=', $scheduleDate);
                              });
                        });
                    })
                    ->first();
                
                if ($unavailability) {
                    $member = Member::find($memberId);
                    $unavailableMembers[] = [
                        'member_id' => $memberId,
                        'member_name' => $member->name,
                        'unavailability' => $unavailability
                    ];
                }
            }
        }

        // Se houver indisponibilidades, retornar aviso mas permitir continuar
        if (!empty($unavailableMembers)) {
            $memberNames = collect($unavailableMembers)->pluck('member_name')->implode(', ');
            $warningMessage = "Atenção! Os seguintes membros têm indisponibilidade cadastrada para esta data: {$memberNames}. Deseja continuar mesmo assim?";
            
            // Se for requisição AJAX, retornar JSON
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'has_unavailabilities' => true,
                    'unavailable_members' => $unavailableMembers,
                    'warning_message' => $warningMessage
                ], 422);
            }
            
            // Se for requisição normal, adicionar aviso na sessão mas permitir continuar
            session()->flash('warning', $warningMessage);
        }

        $schedule = MoriahSchedule::create($data);

        // Adicionar membros com suas funções
        if ($request->has('members')) {
            $membersData = [];
            foreach ($request->members as $memberId) {
                $membersData[$memberId] = ['status' => 'pendente'];
            }
            $schedule->members()->sync($membersData);
            
            // Adicionar funções selecionadas para cada membro
            if ($request->has('member_functions')) {
                foreach ($request->member_functions as $memberId => $functionIds) {
                    $scheduleMember = DB::table('moriah_schedule_members')
                        ->where('moriah_schedule_id', $schedule->id)
                        ->where('member_id', $memberId)
                        ->first();
                    
                    if ($scheduleMember && !empty($functionIds)) {
                        // Remover funções antigas
                        DB::table('moriah_schedule_member_functions')
                            ->where('moriah_schedule_member_id', $scheduleMember->id)
                            ->delete();
                        
                        // Adicionar novas funções
                        foreach ($functionIds as $functionId) {
                            DB::table('moriah_schedule_member_functions')->insert([
                                'moriah_schedule_member_id' => $scheduleMember->id,
                                'moriah_function_id' => $functionId,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
        }

        // Adicionar músicas
        if ($request->has('songs')) {
            $songsData = [];
            foreach ($request->songs as $index => $songId) {
                $songsData[$songId] = ['order' => $index];
            }
            $schedule->songs()->sync($songsData);
        }

        return redirect()->route('moriah.schedules.index')
            ->with('success', 'Escala criada com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $moriahSchedule = MoriahSchedule::with(['event', 'members.moriahFunctions'])->findOrFail($id);
        
        // Carregar músicas diretamente da tabela de relacionamento
        // Isso garante que todas as músicas sejam carregadas corretamente
        $scheduleSongs = DB::table('moriah_schedule_songs')
            ->where('moriah_schedule_id', $moriahSchedule->id)
            ->orderBy('order', 'asc')
            ->get();
        
        // Carregar os dados completos das músicas
        $songsData = [];
        foreach ($scheduleSongs as $scheduleSong) {
            // Buscar a música incluindo soft deleted
            $song = Song::withTrashed()->find($scheduleSong->song_id);
            if ($song) {
                $songsData[] = $song;
            }
        }
        
        // Atribuir as músicas ao objeto mantendo a ordem
        $moriahSchedule->setRelation('songs', collect($songsData));
        
        // Carregar funções selecionadas para cada membro
        $selectedMemberFunctions = [];
        foreach ($moriahSchedule->members as $member) {
            $scheduleMember = DB::table('moriah_schedule_members')
                ->where('moriah_schedule_id', $moriahSchedule->id)
                ->where('member_id', $member->id)
                ->first();
            
            if ($scheduleMember) {
                $selectedFunctions = DB::table('moriah_schedule_member_functions')
                    ->where('moriah_schedule_member_id', $scheduleMember->id)
                    ->join('moriah_functions', 'moriah_schedule_member_functions.moriah_function_id', '=', 'moriah_functions.id')
                    ->pluck('moriah_functions.name')
                    ->toArray();
                
                $selectedMemberFunctions[$member->id] = $selectedFunctions;
            }
        }
        
        return view('moriah.schedules.show', compact('moriahSchedule', 'selectedMemberFunctions'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $moriahSchedule = MoriahSchedule::with(['event', 'members.moriahFunctions', 'songs'])->findOrFail($id);
        
        $month = Carbon::parse($moriahSchedule->date)->month;
        $year = Carbon::parse($moriahSchedule->date)->year;

        // Buscar cultos do mês que ainda não passaram
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();
        $now = Carbon::now();

        $cultos = Event::whereBetween('start_date', [$startDate, $endDate])
            ->where(function($query) {
                $query->where('title', 'like', '%culto%')
                      ->orWhere('title', 'like', '%Culto%')
                      ->orWhereHas('category', function($q) {
                          $q->where('name', 'like', '%culto%')
                            ->orWhere('name', 'like', '%Culto%');
                      });
            })
            ->where(function($query) use ($now, $moriahSchedule) {
                // Incluir cultos futuros OU o culto já selecionado na escala (mesmo que tenha passado)
                $query->where('start_date', '>', $now)
                      ->orWhere('id', $moriahSchedule->event_id);
            })
            ->orderBy('start_date')
            ->get();

        // Buscar apenas membros do Moriah (que têm funções do Moriah)
        $members = Member::where('status', 'ativo')
            ->whereHas('moriahFunctions')
            ->with('moriahFunctions')
            ->orderBy('name')
            ->get();

        // Buscar todas as músicas
        $songs = Song::orderBy('title')->get();

        // Buscar todas as funções do Moriah
        $functions = MoriahFunction::orderBy('order')->orderBy('name')->get();

        // Buscar funções selecionadas para cada membro na escala
        $selectedMemberFunctions = [];
        foreach ($moriahSchedule->members as $member) {
            $scheduleMember = DB::table('moriah_schedule_members')
                ->where('moriah_schedule_id', $moriahSchedule->id)
                ->where('member_id', $member->id)
                ->first();
            
            if ($scheduleMember) {
                $selectedFunctions = DB::table('moriah_schedule_member_functions')
                    ->where('moriah_schedule_member_id', $scheduleMember->id)
                    ->pluck('moriah_function_id')
                    ->toArray();
                
                $selectedMemberFunctions[$member->id] = $selectedFunctions;
            }
        }

        return view('moriah.schedules.edit', compact('moriahSchedule', 'cultos', 'members', 'songs', 'functions', 'selectedMemberFunctions', 'month', 'year'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $moriahSchedule = MoriahSchedule::findOrFail($id);
        
        $request->validate([
            'event_id' => 'nullable|exists:events,id',
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'time' => 'nullable',
            'observations' => 'nullable|string|max:500',
            'status' => 'required|in:rascunho,publicada,concluido',
            'request_confirmation' => 'boolean',
            'members' => 'nullable|array',
            'members.*' => 'exists:members,id',
            'member_functions' => 'nullable|array',
            'member_functions.*' => 'array',
            'member_functions.*.*' => 'exists:moriah_functions,id',
            'songs' => 'nullable|array',
            'songs.*' => 'exists:songs,id',
        ]);

        $data = $request->only([
            'event_id', 'title', 'date', 'time', 'observations', 'status', 'request_confirmation'
        ]);

        $moriahSchedule->update($data);

        // Atualizar membros com suas funções
        if ($request->has('members')) {
            $membersData = [];
            foreach ($request->members as $memberId) {
                // Manter status existente ou criar como pendente
                $existing = $moriahSchedule->members()->where('member_id', $memberId)->first();
                $membersData[$memberId] = ['status' => $existing ? $existing->pivot->status : 'pendente'];
            }
            $moriahSchedule->members()->sync($membersData);
            
            // Atualizar funções selecionadas para cada membro
            if ($request->has('member_functions')) {
                foreach ($request->member_functions as $memberId => $functionIds) {
                    $scheduleMember = DB::table('moriah_schedule_members')
                        ->where('moriah_schedule_id', $moriahSchedule->id)
                        ->where('member_id', $memberId)
                        ->first();
                    
                    if ($scheduleMember) {
                        // Remover funções antigas
                        DB::table('moriah_schedule_member_functions')
                            ->where('moriah_schedule_member_id', $scheduleMember->id)
                            ->delete();
                        
                        // Adicionar novas funções se houver
                        if (!empty($functionIds)) {
                            foreach ($functionIds as $functionId) {
                                DB::table('moriah_schedule_member_functions')->insert([
                                    'moriah_schedule_member_id' => $scheduleMember->id,
                                    'moriah_function_id' => $functionId,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                            }
                        }
                    }
                }
            }
        } else {
            $moriahSchedule->members()->detach();
        }

        // Atualizar músicas
        if ($request->has('songs')) {
            $songsData = [];
            foreach ($request->songs as $index => $songId) {
                $songsData[$songId] = ['order' => $index];
            }
            $moriahSchedule->songs()->sync($songsData);
        } else {
            $moriahSchedule->songs()->detach();
        }

        return redirect()->route('moriah.schedules.index')
            ->with('success', 'Escala atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $moriahSchedule = MoriahSchedule::findOrFail($id);
        $moriahSchedule->delete();
        return redirect()->route('moriah.schedules.index')
            ->with('success', 'Escala excluída com sucesso!');
    }

    /**
     * Confirmar presença do membro na escala
     */
    public function confirmMember($pivotId)
    {
        try {
            $scheduleMember = DB::table('moriah_schedule_members')
                ->where('id', $pivotId)
                ->first();
            
            if (!$scheduleMember) {
                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Registro não encontrado'], 404);
                }
                return redirect()->back()->with('error', 'Registro não encontrado');
            }
            
            // Verificar se o membro logado é o dono do registro
            $user = auth()->user();
            if (!$user) {
                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Usuário não autenticado'], 401);
                }
                return redirect()->route('login')->with('error', 'Por favor, faça login para continuar');
            }
            
            // Verificar se o usuário tem membro associado
            // Carregar o relacionamento member se não estiver carregado
            if (!$user->relationLoaded('member')) {
                $user->load('member');
            }
            
            if (!$user->member) {
                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Usuário não possui membro associado'], 403);
                }
                return redirect()->back()->with('error', 'Usuário não possui membro associado');
            }
            
            // Verificar se o membro logado é o dono do registro
            // Comparar usando string para garantir compatibilidade
            $userMemberId = (string) $user->member->id;
            $scheduleMemberId = (string) $scheduleMember->member_id;
            
            // Log para debug
            \Log::info('Confirmar escala Moriah - Verificação', [
                'pivot_id' => $pivotId,
                'user_id' => $user->id,
                'user_member_id' => $userMemberId,
                'user_member_id_raw' => $user->member->id,
                'user_member_id_type' => gettype($user->member->id),
                'schedule_member_id' => $scheduleMemberId,
                'schedule_member_id_raw' => $scheduleMember->member_id,
                'schedule_member_id_type' => gettype($scheduleMember->member_id),
                'comparison' => $userMemberId === $scheduleMemberId,
                'loose_comparison' => $userMemberId == $scheduleMemberId
            ]);
            
            if ($userMemberId !== $scheduleMemberId) {
                \Log::warning('Tentativa de confirmar escala de outro membro', [
                    'pivot_id' => $pivotId,
                    'user_id' => $user->id,
                    'user_member_id' => $userMemberId,
                    'schedule_member_id' => $scheduleMemberId
                ]);
                
                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'Acesso negado. Você só pode confirmar suas próprias escalas. (Membro logado: ' . $userMemberId . ', Membro da escala: ' . $scheduleMemberId . ')',
                        'debug' => [
                            'user_member_id' => $userMemberId,
                            'schedule_member_id' => $scheduleMemberId,
                            'pivot_id' => $pivotId,
                            'user_id' => $user->id
                        ]
                    ], 403);
                }
                return redirect()->back()->with('error', 'Acesso negado');
            }
            
            DB::table('moriah_schedule_members')
                ->where('id', $pivotId)
                ->update(['status' => 'confirmado', 'updated_at' => now()]);
            
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['success' => true, 'message' => 'Presença confirmada com sucesso!']);
            }
            
            return redirect()->back()->with('success', 'Presença confirmada com sucesso!');
        } catch (\Exception $e) {
            \Log::error('Erro ao confirmar presença do Moriah: ' . $e->getMessage(), [
                'pivot_id' => $pivotId,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Erro ao confirmar presença: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Erro ao confirmar presença. Por favor, tente novamente.');
        }
    }

    /**
     * Recusar presença do membro na escala
     */
    public function rejectMember($pivotId)
    {
        try {
            $scheduleMember = DB::table('moriah_schedule_members')
                ->where('id', $pivotId)
                ->first();
            
            if (!$scheduleMember) {
                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Registro não encontrado'], 404);
                }
                return redirect()->back()->with('error', 'Registro não encontrado');
            }
            
            // Verificar se o membro logado é o dono do registro
            $user = auth()->user();
            if (!$user) {
                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Usuário não autenticado'], 401);
                }
                return redirect()->route('login')->with('error', 'Por favor, faça login para continuar');
            }
            
            // Verificar se o usuário tem membro associado
            // Carregar o relacionamento member se não estiver carregado
            if (!$user->relationLoaded('member')) {
                $user->load('member');
            }
            
            if (!$user->member) {
                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Usuário não possui membro associado'], 403);
                }
                return redirect()->back()->with('error', 'Usuário não possui membro associado');
            }
            
            // Verificar se o membro logado é o dono do registro
            // Comparar usando string para garantir compatibilidade
            $userMemberId = (string) $user->member->id;
            $scheduleMemberId = (string) $scheduleMember->member_id;
            
            if ($userMemberId !== $scheduleMemberId) {
                \Log::warning('Tentativa de recusar escala de outro membro', [
                    'pivot_id' => $pivotId,
                    'user_id' => $user->id,
                    'user_member_id' => $userMemberId,
                    'schedule_member_id' => $scheduleMemberId
                ]);
                
                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json([
                        'success' => false, 
                        'message' => 'Acesso negado. Você só pode recusar suas próprias escalas. (Membro logado: ' . $userMemberId . ', Membro da escala: ' . $scheduleMemberId . ')',
                        'debug' => [
                            'user_member_id' => $userMemberId,
                            'schedule_member_id' => $scheduleMemberId,
                            'pivot_id' => $pivotId
                        ]
                    ], 403);
                }
                return redirect()->back()->with('error', 'Acesso negado');
            }
            
            DB::table('moriah_schedule_members')
                ->where('id', $pivotId)
                ->update(['status' => 'recusado', 'updated_at' => now()]);
            
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['success' => true, 'message' => 'Escala recusada com sucesso!']);
            }
            
            return redirect()->back()->with('success', 'Escala recusada com sucesso!');
        } catch (\Exception $e) {
            \Log::error('Erro ao recusar escala do Moriah: ' . $e->getMessage());
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Erro ao recusar escala: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Erro ao recusar escala. Por favor, tente novamente.');
        }
    }

    /**
     * Atualizar status de confirmação do membro na escala (apenas admin)
     */
    /**
     * Gerar PDF da escala do Moriah usando DOMPDF
     */
    public function generatePdf($id)
    {
        $moriahSchedule = MoriahSchedule::with(['event', 'members.moriahFunctions', 'songs'])->findOrFail($id);
        
        // Verificar se a escala está publicada
        if ($moriahSchedule->status !== 'publicada') {
            return redirect()->route('moriah.schedules.show', $moriahSchedule)
                ->with('error', 'Apenas escalas publicadas podem ser exportadas em PDF.');
        }

        // Carregar funções selecionadas para cada membro
        $selectedMemberFunctions = [];
        $membersWithStatus = [];
        foreach ($moriahSchedule->members as $member) {
            $scheduleMember = DB::table('moriah_schedule_members')
                ->where('moriah_schedule_id', $moriahSchedule->id)
                ->where('member_id', $member->id)
                ->first();
            
            $memberStatus = $scheduleMember ? $scheduleMember->status : 'pendente';
            $membersWithStatus[$member->id] = $memberStatus;
            
            if ($scheduleMember) {
                $selectedFunctions = DB::table('moriah_schedule_member_functions')
                    ->where('moriah_schedule_member_id', $scheduleMember->id)
                    ->join('moriah_functions', 'moriah_schedule_member_functions.moriah_function_id', '=', 'moriah_functions.id')
                    ->pluck('moriah_functions.name')
                    ->toArray();
                
                $selectedMemberFunctions[$member->id] = $selectedFunctions;
            }
        }

        // Organizar músicas com ordem
        $songsWithOrder = [];
        foreach ($moriahSchedule->songs as $song) {
            $pivot = DB::table('moriah_schedule_songs')
                ->where('moriah_schedule_id', $moriahSchedule->id)
                ->where('song_id', $song->id)
                ->first();
            $order = $pivot ? $pivot->order : 999;
            $songsWithOrder[] = [
                'song' => $song,
                'order' => $order
            ];
        }
        usort($songsWithOrder, function($a, $b) {
            return $a['order'] <=> $b['order'];
        });

        // Buscar logo da igreja
        $logoBase64 = null;
        $logoPath = null;
        $logoFileName = 'LOG SS branca.png';
        $logoPublicPath = public_path("img/img/{$logoFileName}");
        
        if (file_exists($logoPublicPath)) {
            $logoPath = $logoPublicPath;
            $imageData = file_get_contents($logoPublicPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($imageData);
        } else {
            $logoExists = Storage::disk('public')->exists('church/logo.png');
            if ($logoExists) {
                $logoPath = storage_path('app/public/church/logo.png');
                $imageData = file_get_contents($logoPath);
                $logoBase64 = 'data:image/png;base64,' . base64_encode($imageData);
            } else {
                $formats = ['jpg', 'jpeg', 'png', 'gif'];
                foreach ($formats as $format) {
                    if (Storage::disk('public')->exists("church/logo.{$format}")) {
                        $logoPath = storage_path("app/public/church/logo.{$format}");
                        $imageData = file_get_contents($logoPath);
                        $mimeType = $format == 'jpg' ? 'jpeg' : $format;
                        $logoBase64 = "data:image/{$mimeType};base64," . base64_encode($imageData);
                        break;
                    }
                }
            }
        }

        $churchName = 'ADELSS';
        $fileName = 'escala-moriah-' . Str::slug($moriahSchedule->title) . '-' . Carbon::parse($moriahSchedule->date)->format('Y-m-d') . '.pdf';

        // Renderizar view do PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.escalas.escala-moriah', [
            'moriahSchedule' => $moriahSchedule,
            'selectedMemberFunctions' => $selectedMemberFunctions,
            'membersWithStatus' => $membersWithStatus,
            'songsWithOrder' => $songsWithOrder,
            'logoPath' => $logoPath,
            'logoBase64' => $logoBase64,
            'churchName' => $churchName,
            'generatedAt' => now(),
        ])->setPaper('A4', 'portrait')
          ->setOption('enable-local-file-access', true);

        return $pdf->download($fileName);
    }

    public function updateMemberStatus(Request $request, $pivotId)
    {
        // Verificar se é admin
        $user = auth()->user();
        if (!$user || !$user->is_admin) {
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Acesso negado. Apenas administradores podem alterar o status.'], 403);
            }
            return redirect()->back()->with('error', 'Acesso negado');
        }

        try {
            // Validar e obter o status
            $validated = $request->validate([
                'status' => 'required|in:pendente,confirmado,recusado,cancelado'
            ]);
            
            $status = $validated['status']; // Usar o valor validado

            $scheduleMember = DB::table('moriah_schedule_members')
                ->where('id', $pivotId)
                ->first();
            
            if (!$scheduleMember) {
                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Registro não encontrado'], 404);
                }
                return redirect()->back()->with('error', 'Registro não encontrado');
            }
            
            // Garantir que o status seja uma string válida
            $validStatuses = ['pendente', 'confirmado', 'recusado', 'cancelado'];
            if (!in_array($status, $validStatuses)) {
                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Status inválido'], 422);
                }
                return redirect()->back()->with('error', 'Status inválido');
            }
            
            // Usar Eloquent através do relacionamento para garantir binding correto
            // Primeiro, vamos buscar a escala e o membro
            $schedule = MoriahSchedule::find($scheduleMember->moriah_schedule_id);
            if ($schedule) {
                $schedule->members()->updateExistingPivot($scheduleMember->member_id, [
                    'status' => $status,
                    'updated_at' => Carbon::now()
                ]);
                $updated = 1;
            } else {
                // Fallback: usar query SQL direta com CONCAT para forçar string
                $updated = DB::update(
                    "UPDATE moriah_schedule_members SET status = CONCAT('', ?), updated_at = ? WHERE id = ?",
                    [$status, Carbon::now(), $pivotId]
                );
            }
            
            if ($updated === 0) {
                if (request()->expectsJson() || request()->ajax()) {
                    return response()->json(['success' => false, 'message' => 'Nenhum registro foi atualizado'], 404);
                }
                return redirect()->back()->with('error', 'Nenhum registro foi atualizado');
            }
            
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Status atualizado com sucesso!',
                    'status' => $request->status
                ]);
            }
            
            return redirect()->back()->with('success', 'Status atualizado com sucesso!');
        } catch (\Exception $e) {
            \Log::error('Erro ao atualizar status do membro na escala do Moriah: ' . $e->getMessage());
            if (request()->expectsJson() || request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Erro ao atualizar status: ' . $e->getMessage()], 500);
            }
            return redirect()->back()->with('error', 'Erro ao atualizar status. Por favor, tente novamente.');
        }
    }
}
