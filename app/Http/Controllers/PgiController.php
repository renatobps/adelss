<?php

namespace App\Http\Controllers;

use App\Models\Enquete;
use App\Models\Pgi;
use App\Models\Member;
use App\Services\EnqueteService;
use App\Services\NotificacaoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class PgiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user?->is_admin ?? false;
        $member = $user?->member;
        
        // Verificar se tem permissão para ver todos os PGIs
        $hasViewPermission = $isAdmin || 
                            $user->hasPermission('pgis.index.view') || 
                            $user->hasPermission('pgis.index.manage');
        
        $query = Pgi::with(['leader1', 'leader2', 'leaderTraining1', 'leaderTraining2', 'members']);

        // Se não for admin e não tiver permissão para ver todos, mostrar apenas PGIs relacionados ao membro
        if (!$hasViewPermission && $member) {
            $query->where(function($q) use ($member) {
                // PGI do qual faz parte como membro
                if ($member->pgi_id) {
                    $q->where('id', $member->pgi_id);
                }
                // OU PGI do qual é líder ou líder em treinamento
                $q->orWhere('leader_1_id', $member->id)
                  ->orWhere('leader_2_id', $member->id)
                  ->orWhere('leader_training_1_id', $member->id)
                  ->orWhere('leader_training_2_id', $member->id);
            });
        }

        // Busca
        if ($request->has('search') && $request->search) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('address', 'like', "%{$request->search}%")
                  ->orWhere('neighborhood', 'like', "%{$request->search}%");
            });
        }

        // Filtro por perfil/sexo (pode ser array de checkboxes)
        if ($request->has('gender') && is_array($request->gender) && count($request->gender) > 0) {
            $query->whereIn('profile', $request->gender);
        } elseif ($request->has('profile') && $request->profile) {
            $query->where('profile', $request->profile);
        }

        // Filtro por categorias (caso seja implementado no futuro)
        // Por enquanto, categorias são apenas para visualização

        // Filtro por dia da semana
        if ($request->has('day_of_week') && $request->day_of_week) {
            $query->where('day_of_week', $request->day_of_week);
        }

        // Filtro por horário
        if ($request->has('time_schedule') && $request->time_schedule) {
            $query->where('time_schedule', $request->time_schedule);
        }

        // Ordenação
        $sortBy = $request->get('sort_by', 'name');
        $sortOrder = $request->get('sort_order', 'asc');
        $query->orderBy($sortBy, $sortOrder);

        $pgis = $query->paginate(15);

        return view('pgis.index', compact('pgis'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $members = Member::orderBy('name')->get();
        return view('pgis.create', compact('members'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'opening_date' => 'nullable|date',
            'day_of_week' => 'nullable|in:segunda,terça,quarta,quinta,sexta,sábado,domingo',
            'profile' => 'nullable|in:Masculino,Feminino,Misto',
            'time_schedule' => 'nullable|in:Manhã,Tarde,Noite',
            'leader_1_id' => 'nullable|exists:members,id',
            'leader_2_id' => 'nullable|exists:members,id',
            'leader_training_1_id' => 'nullable|exists:members,id',
            'leader_training_2_id' => 'nullable|exists:members,id',
            'address' => 'nullable|string|max:255',
            'neighborhood' => 'nullable|string|max:100',
            'number' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
        ], [
            'name.required' => 'O campo nome do PGI é obrigatório.',
            'name.max' => 'O nome do PGI não pode ter mais de 255 caracteres.',
            'opening_date.date' => 'A data de abertura deve ser uma data válida.',
            'day_of_week.in' => 'O dia da semana selecionado é inválido.',
            'profile.in' => 'O perfil selecionado é inválido.',
            'time_schedule.in' => 'O horário selecionado é inválido.',
            'leader_1_id.exists' => 'O líder 1 selecionado não existe.',
            'leader_2_id.exists' => 'O líder 2 selecionado não existe.',
            'leader_training_1_id.exists' => 'O líder em treinamento 1 selecionado não existe.',
            'leader_training_2_id.exists' => 'O líder em treinamento 2 selecionado não existe.',
            'logo.image' => 'O logo deve ser uma imagem.',
            'logo.mimes' => 'O logo deve ser nos formatos: jpeg, png, jpg, gif ou svg.',
            'logo.max' => 'O logo não pode ter mais de 2MB.',
            'banner.image' => 'O banner deve ser uma imagem.',
            'banner.mimes' => 'O banner deve ser nos formatos: jpeg, png, jpg, gif ou svg.',
            'banner.max' => 'O banner não pode ter mais de 2MB.',
        ]);

        // Upload do logo
        if ($request->hasFile('logo')) {
            $validated['logo_url'] = $request->file('logo')->store('pgis/logos', 'public');
        }

        // Upload do banner
        if ($request->hasFile('banner')) {
            $validated['banner_url'] = $request->file('banner')->store('pgis/banners', 'public');
        }

        Pgi::create($validated);

        return redirect()->route('pgis.index')
            ->with('success', 'PGI cadastrado com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Pgi $pgi)
    {
        $user = auth()->user();
        $isAdmin = $user?->is_admin ?? false;
        $member = $user?->member;
        
        // Se não for admin, verificar se faz parte deste PGI ou se é líder
        if (!$isAdmin && $member) {
            // Verificar se tem permissão, se faz parte do PGI ou se é líder/líder em treinamento
            $hasPermission = $user->hasPermission('pgis.index.view') || 
                            $user->hasPermission('pgis.index.manage');
            $isMemberOfPgi = $member->pgi_id == $pgi->id;
            $isLeader = $pgi->isLeader($member);
            
            if (!$hasPermission && !$isMemberOfPgi && !$isLeader) {
                abort(403, 'Acesso negado. Você não tem permissão para visualizar este PGI.');
            }
        }
        
        $pgi->load(['leader1', 'leader2', 'leaderTraining1', 'leaderTraining2', 'members']);
        
        // Carregar reuniões para o dashboard
        $meetings = $pgi->meetings()
            ->with(['attendances.member'])
            ->orderBy('meeting_date', 'desc')
            ->limit(12)
            ->get();
        
        // Preparar dados para o gráfico
        $chartData = $pgi->meetings()
            ->orderBy('meeting_date', 'asc')
            ->get()
            ->map(function ($meeting) {
                return [
                    'date' => $meeting->meeting_date->format('d/m/Y'),
                    'participants' => $meeting->participants_count,
                    'visitors' => $meeting->visitors_count,
                    'total' => $meeting->participants_count + $meeting->visitors_count,
                ];
            });
        
        $enquetes = Enquete::ativas()->orderByDesc('created_at')->get(['id', 'titulo']);

        return view('pgis.show', compact('pgi', 'meetings', 'chartData', 'enquetes'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Pgi $pgi)
    {
        $members = Member::orderBy('name')->get();
        return view('pgis.edit', compact('pgi', 'members'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Pgi $pgi)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'opening_date' => 'nullable|date',
            'day_of_week' => 'nullable|in:segunda,terça,quarta,quinta,sexta,sábado,domingo',
            'profile' => 'nullable|in:Masculino,Feminino,Misto',
            'time_schedule' => 'nullable|in:Manhã,Tarde,Noite',
            'leader_1_id' => 'nullable|exists:members,id',
            'leader_2_id' => 'nullable|exists:members,id',
            'leader_training_1_id' => 'nullable|exists:members,id',
            'leader_training_2_id' => 'nullable|exists:members,id',
            'address' => 'nullable|string|max:255',
            'neighborhood' => 'nullable|string|max:100',
            'number' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
        ], [
            'name.required' => 'O campo nome do PGI é obrigatório.',
            'name.max' => 'O nome do PGI não pode ter mais de 255 caracteres.',
            'opening_date.date' => 'A data de abertura deve ser uma data válida.',
            'day_of_week.in' => 'O dia da semana selecionado é inválido.',
            'profile.in' => 'O perfil selecionado é inválido.',
            'time_schedule.in' => 'O horário selecionado é inválido.',
            'leader_1_id.exists' => 'O líder 1 selecionado não existe.',
            'leader_2_id.exists' => 'O líder 2 selecionado não existe.',
            'leader_training_1_id.exists' => 'O líder em treinamento 1 selecionado não existe.',
            'leader_training_2_id.exists' => 'O líder em treinamento 2 selecionado não existe.',
            'logo.image' => 'O logo deve ser uma imagem.',
            'logo.mimes' => 'O logo deve ser nos formatos: jpeg, png, jpg, gif ou svg.',
            'logo.max' => 'O logo não pode ter mais de 2MB.',
            'banner.image' => 'O banner deve ser uma imagem.',
            'banner.mimes' => 'O banner deve ser nos formatos: jpeg, png, jpg, gif ou svg.',
            'banner.max' => 'O banner não pode ter mais de 2MB.',
        ]);

        // Upload do logo
        if ($request->hasFile('logo')) {
            // Remove logo antigo se existir
            if ($pgi->logo_url) {
                Storage::disk('public')->delete($pgi->logo_url);
            }
            $validated['logo_url'] = $request->file('logo')->store('pgis/logos', 'public');
        }

        // Upload do banner
        if ($request->hasFile('banner')) {
            // Remove banner antigo se existir
            if ($pgi->banner_url) {
                Storage::disk('public')->delete($pgi->banner_url);
            }
            $validated['banner_url'] = $request->file('banner')->store('pgis/banners', 'public');
        }

        $pgi->update($validated);

        return redirect()->route('pgis.index')
            ->with('success', 'PGI atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Pgi $pgi)
    {
        try {
            // Verifica se há membros vinculados a este PGI
            if ($pgi->members()->count() > 0) {
                return redirect()->route('pgis.index')
                    ->with('error', 'Não é possível excluir este PGI pois existem membros vinculados a ele.');
            }

            // Remove logo e banner se existirem
            if ($pgi->logo_url) {
                Storage::disk('public')->delete($pgi->logo_url);
            }
            if ($pgi->banner_url) {
                Storage::disk('public')->delete($pgi->banner_url);
            }

            $pgi->delete();

            return redirect()->route('pgis.index')
                ->with('success', 'PGI excluído com sucesso!');
        } catch (\Exception $e) {
            return redirect()->route('pgis.index')
                ->with('error', 'Erro ao excluir PGI. Por favor, tente novamente.');
        }
    }

    /**
     * Anexar membros ao PGI
     */
    public function attachMembers(Request $request, Pgi $pgi)
    {
        $validated = $request->validate([
            'members' => 'required|array|min:1',
            'members.*' => 'exists:members,id',
        ], [
            'members.required' => 'Selecione pelo menos um membro para adicionar.',
            'members.array' => 'Os membros devem ser uma lista válida.',
            'members.min' => 'Selecione pelo menos um membro para adicionar.',
            'members.*.exists' => 'Um ou mais membros selecionados não existem.',
        ]);

        // Anexar membros ao PGI
        foreach ($validated['members'] as $memberId) {
            $member = Member::find($memberId);
            if ($member && !$pgi->members->contains($memberId)) {
                $member->pgi_id = $pgi->id;
                $member->save();
            }
        }

        return redirect()->route('pgis.show', $pgi)
            ->with('success', 'Membros adicionados ao PGI com sucesso!');
    }

    /**
     * Desanexar membro do PGI
     */
    public function detachMember(Pgi $pgi, Member $member)
    {
        if ($member->pgi_id == $pgi->id) {
            $member->pgi_id = null;
            $member->save();

            return redirect()->route('pgis.show', $pgi)
                ->with('success', 'Membro removido do PGI com sucesso!');
        }

        return redirect()->route('pgis.show', $pgi)
            ->with('error', 'Membro não está vinculado a este PGI.');
    }

    /**
     * Atualizar apenas o logo do PGI
     */
    public function updateLogo(Request $request, Pgi $pgi)
    {
        $validated = $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ], [
            'logo.required' => 'Selecione uma imagem para o logo.',
            'logo.image' => 'O logo deve ser uma imagem.',
            'logo.mimes' => 'O logo deve ser nos formatos: jpeg, png, jpg, gif ou svg.',
            'logo.max' => 'O logo não pode ter mais de 2MB.',
        ]);

        // Remove logo antigo se existir
        if ($pgi->logo_url) {
            Storage::disk('public')->delete($pgi->logo_url);
        }

        // Upload do novo logo
        $pgi->logo_url = $request->file('logo')->store('pgis/logos', 'public');
        $pgi->save();

        return redirect()->route('pgis.show', $pgi)
            ->with('success', 'Logo atualizado com sucesso!');
    }

    /**
     * Atualizar apenas o banner do PGI
     */
    public function updateBanner(Request $request, Pgi $pgi)
    {
        $validated = $request->validate([
            'banner' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ], [
            'banner.required' => 'Selecione uma imagem para o banner.',
            'banner.image' => 'O banner deve ser uma imagem.',
            'banner.mimes' => 'O banner deve ser nos formatos: jpeg, png, jpg, gif ou svg.',
            'banner.max' => 'O banner não pode ter mais de 2MB.',
        ]);

        // Remove banner antigo se existir
        if ($pgi->banner_url) {
            Storage::disk('public')->delete($pgi->banner_url);
        }

        // Upload do novo banner
        $pgi->banner_url = $request->file('banner')->store('pgis/banners', 'public');
        $pgi->save();

        return redirect()->route('pgis.show', $pgi)
            ->with('success', 'Banner atualizado com sucesso!');
    }

    /**
     * Envia notificações WhatsApp para os participantes do PGI.
     */
    public function enviarNotificacao(Request $request, Pgi $pgi)
    {
        $request->validate([
            'tipo_envio' => 'required|in:texto,imagem,video,enquete',
            'mensagem' => 'nullable|string|max:4096',
            'arquivo' => 'nullable|file|mimes:jpeg,jpg,png,webp,mp4,mov,avi|max:51200',
            'enquete_id' => 'nullable|integer|exists:notificacao_enquetes,id',
        ]);

        $user = auth()->user();
        $isAdmin = $user?->is_admin ?? false;
        $member = $user?->member;
        $isLeader = $member && $pgi->isLeader($member);
        if (! $isAdmin && ! $isLeader) {
            abort(403, 'Apenas administradores ou líderes podem enviar notificações para o PGI.');
        }

        $tipo = $request->input('tipo_envio');
        $mensagem = (string) $request->input('mensagem', '');
        $members = $pgi->members()->whereNotNull('phone')->where('phone', '!=', '')->get();

        if ($members->isEmpty()) {
            return back()->withErrors(['destinatarios' => 'Este PGI não possui participantes com telefone cadastrado.']);
        }

        if ($tipo === 'enquete') {
            $request->validate([
                'enquete_id' => 'required|integer|exists:notificacao_enquetes,id',
            ]);
            $enquete = Enquete::findOrFail((int) $request->input('enquete_id'));
            $memberIds = $members->pluck('id')->all();
            $totais = app(EnqueteService::class)->enviarEnquete($enquete, $memberIds, []);

            return back()->with('success', "Enquete enviada para participantes do PGI: {$totais['enviadas']} enviadas, {$totais['erros']} erros.");
        }

        $service = app(NotificacaoService::class);
        $totais = ['enviadas' => 0, 'erros' => 0, 'total' => $members->count()];

        if ($tipo === 'texto') {
            if (trim($mensagem) === '') {
                return back()->withErrors(['mensagem' => 'Digite uma mensagem para envio em texto.'])->withInput();
            }
            $totais = $service->enviarParaMembros($members, $mensagem);
        }

        if (in_array($tipo, ['imagem', 'video'], true)) {
            if (! $request->hasFile('arquivo')) {
                return back()->withErrors(['arquivo' => 'Selecione um arquivo para envio.'])->withInput();
            }
            $totais = $service->enviarMidiaParaMembros($members, $request->file('arquivo'), $tipo, $mensagem);
        }

        return back()->with('success', "Envio concluído para participantes do PGI: {$totais['enviadas']} enviadas, {$totais['erros']} erros.");
    }
}

