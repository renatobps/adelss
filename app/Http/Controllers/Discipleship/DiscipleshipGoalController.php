<?php

namespace App\Http\Controllers\Discipleship;

use App\Http\Controllers\Controller;
use App\Helpers\HtmlHelper;
use App\Models\Discipleship\DiscipleshipGoal;
use App\Models\Discipleship\DiscipleshipMember;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class DiscipleshipGoalController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $memberId = $request->get('discipleship_member_id');
        $status = $request->get('status', 'em_andamento');
        
        $query = DiscipleshipGoal::with(['discipleshipMember.member']);
        
        if ($memberId) {
            $query->where('discipleship_member_id', $memberId);
        }
        
        if ($status === 'concluido') {
            $query->concluidos();
        } else {
            $query->emAndamento();
        }
        
        $goals = $query->orderBy('prazo', 'asc')->paginate(15);
        
        return view('discipleship.goals.index', compact('goals', 'memberId', 'status'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $memberId = $request->get('discipleship_member_id');
        $members = DiscipleshipMember::ativos()->with('member')->get();
        
        return view('discipleship.goals.create', compact('members', 'memberId'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'discipleship_member_id' => 'required|exists:discipleship_members,id',
            'tipo' => 'required|in:espiritual,material',
            'descricao' => 'required|string|max:255',
            'prazo' => 'nullable|date',
            'status' => 'required|in:em_andamento,concluido,pausado',
            'observacao' => 'nullable|string',
            // Área de Propósito
            'quantidade_dias' => 'nullable|integer|min:1|max:30',
            'restricoes' => 'nullable|array',
            'restricoes.*' => 'in:filmes,series,instagram,youtube,tiktok,facebook',
            // Área de Jejum
            'tipo_jejum' => 'nullable|in:nenhum,total,parcial',
            'horas_jejum_total' => 'nullable|integer|min:6|max:72|required_if:tipo_jejum,total',
            'dias_jejum_parcial' => 'nullable|integer|min:1|max:30|required_if:tipo_jejum,parcial',
            'alimentos_retirados' => 'nullable|array|required_if:tipo_jejum,parcial',
            'alimentos_retirados.*' => 'in:derivados_trigo,guloseimas,almoco,jantar,cafe_manha',
            // Área de Oração
            'periodos_oracao_dia' => 'nullable|integer|in:1,2,3',
            'minutos_oracao_periodo' => 'nullable|integer|min:1|required_with:periodos_oracao_dia',
            // Área de Estudo da Palavra
            'livro_biblia' => 'nullable|string|max:255',
            'capitulos_por_dia' => 'nullable|integer|min:1|required_with:livro_biblia',
        ], [
            'discipleship_member_id.required' => 'O membro é obrigatório.',
            'tipo.required' => 'O tipo é obrigatório.',
            'descricao.required' => 'A descrição é obrigatória.',
            'horas_jejum_total.required_if' => 'A quantidade de horas é obrigatória para jejum total.',
            'dias_jejum_parcial.required_if' => 'A quantidade de dias é obrigatória para jejum parcial.',
            'alimentos_retirados.required_if' => 'Selecione pelo menos um alimento para jejum parcial.',
            'minutos_oracao_periodo.required_with' => 'A quantidade de minutos é obrigatória quando período de oração é informado.',
            'capitulos_por_dia.required_with' => 'A quantidade de capítulos é obrigatória quando livro é informado.',
        ]);

        if (!empty($validated['observacao'])) {
            $validated['observacao'] = HtmlHelper::sanitize($validated['observacao']);
        }

        DiscipleshipGoal::create($validated);

        return redirect()->route('discipleship.goals.index', ['discipleship_member_id' => $validated['discipleship_member_id']])
            ->with('success', 'Propósito criado com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(DiscipleshipGoal $goal)
    {
        $goal->load(['discipleshipMember.member', 'discipleshipMember.cycle', 'discipleshipMember.discipulador']);
        
        return view('discipleship.goals.show', compact('goal'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(DiscipleshipGoal $goal)
    {
        $members = DiscipleshipMember::ativos()->with('member')->get();
        
        return view('discipleship.goals.edit', compact('goal', 'members'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DiscipleshipGoal $goal)
    {
        $validated = $request->validate([
            'discipleship_member_id' => 'required|exists:discipleship_members,id',
            'tipo' => 'required|in:espiritual,material',
            'descricao' => 'required|string|max:255',
            'prazo' => 'nullable|date',
            'status' => 'required|in:em_andamento,concluido,pausado',
            'observacao' => 'nullable|string',
            // Área de Propósito
            'quantidade_dias' => 'nullable|integer|min:1|max:30',
            'restricoes' => 'nullable|array',
            'restricoes.*' => 'in:filmes,series,instagram,youtube,tiktok,facebook',
            // Área de Jejum
            'tipo_jejum' => 'nullable|in:nenhum,total,parcial',
            'horas_jejum_total' => 'nullable|integer|min:6|max:72|required_if:tipo_jejum,total',
            'dias_jejum_parcial' => 'nullable|integer|min:1|max:30|required_if:tipo_jejum,parcial',
            'alimentos_retirados' => 'nullable|array|required_if:tipo_jejum,parcial',
            'alimentos_retirados.*' => 'in:derivados_trigo,guloseimas,almoco,jantar,cafe_manha',
            // Área de Oração
            'periodos_oracao_dia' => 'nullable|integer|in:1,2,3',
            'minutos_oracao_periodo' => 'nullable|integer|min:1|required_with:periodos_oracao_dia',
            // Área de Estudo da Palavra
            'livro_biblia' => 'nullable|string|max:255',
            'capitulos_por_dia' => 'nullable|integer|min:1|required_with:livro_biblia',
        ], [
            'discipleship_member_id.required' => 'O membro é obrigatório.',
            'tipo.required' => 'O tipo é obrigatório.',
            'descricao.required' => 'A descrição é obrigatória.',
            'horas_jejum_total.required_if' => 'A quantidade de horas é obrigatória para jejum total.',
            'dias_jejum_parcial.required_if' => 'A quantidade de dias é obrigatória para jejum parcial.',
            'alimentos_retirados.required_if' => 'Selecione pelo menos um alimento para jejum parcial.',
            'minutos_oracao_periodo.required_with' => 'A quantidade de minutos é obrigatória quando período de oração é informado.',
            'capitulos_por_dia.required_with' => 'A quantidade de capítulos é obrigatória quando livro é informado.',
        ]);

        if (!empty($validated['observacao'])) {
            $validated['observacao'] = HtmlHelper::sanitize($validated['observacao']);
        }

        $goal->update($validated);

        return redirect()->route('discipleship.goals.index', ['discipleship_member_id' => $goal->discipleship_member_id])
            ->with('success', 'Propósito atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(DiscipleshipGoal $goal)
    {
        $memberId = $goal->discipleship_member_id;
        $goal->delete();

        return redirect()->route('discipleship.goals.index', ['discipleship_member_id' => $memberId])
            ->with('success', 'Propósito excluído com sucesso!');
    }

    /**
     * Gerar PDF do propósito
     */
    public function generatePdf(DiscipleshipGoal $goal)
    {
        $goal->load(['discipleshipMember.member', 'discipleshipMember.cycle', 'discipleshipMember.discipulador']);
        
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
            }
        }

        $churchName = 'ADELSS';
        $fileName = 'proposito-' . Str::slug($goal->descricao) . '-' . Carbon::now()->format('Y-m-d') . '.pdf';

        // Observação: processar emojis conforme config (DomPDF não exibe emojis nativamente)
        $observacaoForPdf = $goal->observacao
            ? HtmlHelper::prepareForPdf($goal->observacao, config('dompdf.emoji_in_pdf', false))
            : null;

        // Renderizar view do PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('discipleship.goals.pdf', [
            'goal' => $goal,
            'observacaoForPdf' => $observacaoForPdf,
            'logoBase64' => $logoBase64,
            'churchName' => $churchName,
            'generatedAt' => now(),
        ])->setPaper('A4', 'portrait')
          ->setOption('enable-local-file-access', true);

        return $pdf->download($fileName);
    }
}
