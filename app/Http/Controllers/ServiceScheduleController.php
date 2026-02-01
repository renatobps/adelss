<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ServiceSchedule;
use App\Models\ServiceScheduleArea;
use App\Models\ServiceScheduleVolunteer;
use App\Models\ServiceArea;
use App\Models\Volunteer;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ServiceScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = ServiceSchedule::with(['areas.serviceArea', 'event']);

        // Filtros
        if ($request->has('area') && $request->area) {
            $query->whereHas('areas', function($q) use ($request) {
                $q->where('service_area_id', $request->area);
            });
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from') && $request->date_from) {
            $query->where('date', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->where('date', '<=', $request->date_to);
        }

        $schedules = $query->orderBy('date', 'desc')
                          ->orderBy('start_time', 'desc')
                          ->paginate(15);

        $serviceAreas = ServiceArea::active()->with('leader')->get();

        return view('service-schedules.index', compact('schedules', 'serviceAreas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $step = $request->get('step', 1);
        
        $serviceAreas = ServiceArea::active()->with('leader')->get();
        
        // Filtrar eventos dos próximos 37 dias a partir da data atual
        $startDate = \Carbon\Carbon::now()->startOfDay();
        $endDate = \Carbon\Carbon::now()->addDays(37)->endOfDay();
        
        $events = Event::with('category')
                      ->whereBetween('start_date', [$startDate, $endDate])
                      ->orderBy('start_date', 'asc')
                      ->orderBy('title', 'asc')
                      ->get();
        $members = \App\Models\Member::orderBy('name')->get();

        // Dados da sessão para o wizard
        $wizardData = session('schedule_wizard', []);

        return view('service-schedules.create', compact('step', 'serviceAreas', 'events', 'wizardData', 'members'));
    }

    /**
     * Store step 1 - Dados Gerais
     */
    public function storeStep1(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'type' => 'required|in:culto,evento',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'event_id' => 'nullable|exists:events,id',
        ], [
            'title.required' => 'O campo nome da escala é obrigatório.',
            'date.required' => 'O campo data é obrigatório.',
            'start_time.required' => 'O campo horário é obrigatório.',
            'type.required' => 'O campo tipo é obrigatório.',
        ]);

        // Salvar na sessão
        $wizardData = session('schedule_wizard', []);
        $wizardData['step1'] = $validated;
        session(['schedule_wizard' => $wizardData]);

        return redirect()->route('voluntarios.escalas.create', ['step' => 2]);
    }

    /**
     * Store step 2 - Áreas de Serviço
     */
    public function storeStep2(Request $request)
    {
        $validated = $request->validate([
            'areas' => 'required|array|min:1',
            'areas.*.service_area_id' => 'required|exists:service_areas,id',
            'areas.*.required_quantity' => 'required|integer|min:1',
            'areas.*.responsible_id' => 'nullable|exists:members,id',
        ], [
            'areas.required' => 'Selecione pelo menos uma área de serviço.',
            'areas.*.service_area_id.required' => 'A área de serviço é obrigatória.',
            'areas.*.required_quantity.required' => 'A quantidade necessária é obrigatória.',
            'areas.*.required_quantity.min' => 'A quantidade mínima é 1.',
        ]);

        // Salvar na sessão
        $wizardData = session('schedule_wizard', []);
        $wizardData['step2'] = $validated;
        session(['schedule_wizard' => $wizardData]);

        return redirect()->route('voluntarios.escalas.create', ['step' => 3]);
    }

    /**
     * Store step 3 - Seleção de Voluntários
     */
    public function storeStep3(Request $request)
    {
        $wizardData = session('schedule_wizard', []);
        
        if (!isset($wizardData['step2'])) {
            return redirect()->route('voluntarios.escalas.create', ['step' => 2])
                ->with('error', 'Complete as etapas anteriores primeiro.');
        }

        $validated = $request->validate([
            'volunteers' => 'required|array',
            'volunteers.*.schedule_area_id' => 'required|integer',
            'volunteers.*.volunteer_ids' => 'required|array|min:1',
            'volunteers.*.volunteer_ids.*' => 'exists:volunteers,id',
        ], [
            'volunteers.required' => 'Selecione pelo menos um voluntário.',
            'volunteers.*.volunteer_ids.required' => 'Selecione pelo menos um voluntário para cada área.',
            'volunteers.*.volunteer_ids.min' => 'Selecione pelo menos um voluntário para cada área.',
        ]);

        // Salvar na sessão
        $wizardData['step3'] = $validated;
        session(['schedule_wizard' => $wizardData]);

        return redirect()->route('voluntarios.escalas.create', ['step' => 4]);
    }

    /**
     * Store step 4 - Revisão e Publicação
     */
    public function store(Request $request)
    {
        $wizardData = session('schedule_wizard', []);
        
        if (!isset($wizardData['step1']) || !isset($wizardData['step2']) || !isset($wizardData['step3'])) {
            return redirect()->route('voluntarios.escalas.create')
                ->with('error', 'Complete todas as etapas primeiro.');
        }

        $validated = $request->validate([
            'status' => 'required|in:rascunho,publicada',
        ]);

        DB::beginTransaction();
        try {
            // Criar escala
            $schedule = ServiceSchedule::create([
                'title' => $wizardData['step1']['title'],
                'date' => $wizardData['step1']['date'],
                'start_time' => $wizardData['step1']['start_time'],
                'type' => $wizardData['step1']['type'],
                'status' => $validated['status'],
                'location' => $wizardData['step1']['location'] ?? null,
                'notes' => $wizardData['step1']['notes'] ?? null,
                'event_id' => $wizardData['step1']['event_id'] ?? null,
            ]);

            // Criar áreas
            foreach ($wizardData['step2']['areas'] as $index => $areaData) {
                $scheduleArea = ServiceScheduleArea::create([
                    'schedule_id' => $schedule->id,
                    'service_area_id' => $areaData['service_area_id'],
                    'required_quantity' => $areaData['required_quantity'],
                    'responsible_id' => $areaData['responsible_id'] ?? null,
                ]);

                // Criar voluntários para esta área (usando o índice do array)
                $volunteerData = collect($wizardData['step3']['volunteers'])
                    ->firstWhere('schedule_area_id', $index);

                if ($volunteerData && isset($volunteerData['volunteer_ids'])) {
                    foreach ($volunteerData['volunteer_ids'] as $volunteerId) {
                        ServiceScheduleVolunteer::create([
                            'schedule_area_id' => $scheduleArea->id,
                            'volunteer_id' => $volunteerId,
                            'status' => 'pendente',
                        ]);
                    }
                }
            }

            DB::commit();

            // Limpar sessão
            session()->forget('schedule_wizard');

            return redirect()->route('voluntarios.escalas.show', $schedule)
                ->with('success', 'Escala criada com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Erro ao criar escala: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceSchedule $escala)
    {
        $escala->load([
            'areas.serviceArea',
            'areas.responsible',
            'areas.volunteers.volunteer.member',
            'event'
        ]);

        return view('service-schedules.show', compact('escala'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ServiceSchedule $escala)
    {
        if ($escala->status === 'publicada') {
            return redirect()->route('voluntarios.escalas.show', $escala)
                ->with('error', 'Não é possível editar uma escala publicada.');
        }

        $escala->load([
            'areas.serviceArea',
            'areas.responsible',
            'areas.volunteers.volunteer.member',
            'event'
        ]);

        $serviceAreas = ServiceArea::active()->with('leader')->get();
        
        // Filtrar eventos dos próximos 37 dias a partir da data atual
        $startDate = \Carbon\Carbon::now()->startOfDay();
        $endDate = \Carbon\Carbon::now()->addDays(37)->endOfDay();
        
        $events = Event::with('category')
                      ->whereBetween('start_date', [$startDate, $endDate])
                      ->orderBy('start_date', 'asc')
                      ->orderBy('title', 'asc')
                      ->get();
        $members = \App\Models\Member::orderBy('name')->get();

        return view('service-schedules.edit', compact('escala', 'serviceAreas', 'events', 'members'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServiceSchedule $escala)
    {
        if ($escala->status === 'publicada') {
            return redirect()->route('voluntarios.escalas.show', $escala)
                ->with('error', 'Não é possível editar uma escala publicada.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'type' => 'required|in:culto,evento',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'event_id' => 'nullable|exists:events,id',
            'areas' => 'required|array|min:1',
            'areas.*.id' => 'nullable|exists:service_schedule_areas,id',
            'areas.*.service_area_id' => 'required|exists:service_areas,id',
            'areas.*.required_quantity' => 'required|integer|min:1',
            'areas.*.responsible_id' => 'nullable|exists:members,id',
            'areas.*.volunteer_ids' => 'nullable|array',
            'areas.*.volunteer_ids.*' => 'exists:volunteers,id',
        ], [
            'areas.required' => 'Selecione pelo menos uma área de serviço.',
            'areas.*.service_area_id.required' => 'A área de serviço é obrigatória.',
            'areas.*.required_quantity.required' => 'A quantidade necessária é obrigatória.',
            'areas.*.required_quantity.min' => 'A quantidade mínima é 1.',
        ]);

        DB::beginTransaction();
        try {
            // Atualizar dados gerais
            $escala->update([
                'title' => $validated['title'],
                'date' => $validated['date'],
                'start_time' => $validated['start_time'],
                'type' => $validated['type'],
                'location' => $validated['location'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'event_id' => $validated['event_id'] ?? null,
            ]);

            // Obter IDs das áreas existentes que foram mantidas
            $existingAreaIds = collect($validated['areas'])
                ->pluck('id')
                ->filter()
                ->toArray();

            // Remover áreas que não estão mais no formulário
            $escala->areas()->whereNotIn('id', $existingAreaIds)->delete();

            // Atualizar ou criar áreas
            foreach ($validated['areas'] as $areaData) {
                if (isset($areaData['id']) && $areaData['id']) {
                    // Atualizar área existente
                    $scheduleArea = ServiceScheduleArea::find($areaData['id']);
                    if ($scheduleArea) {
                        $scheduleArea->update([
                            'service_area_id' => $areaData['service_area_id'],
                            'required_quantity' => $areaData['required_quantity'],
                            'responsible_id' => $areaData['responsible_id'] ?? null,
                        ]);

                        // Atualizar voluntários
                        $volunteerIds = $areaData['volunteer_ids'] ?? [];
                        $scheduleArea->volunteers()->whereNotIn('volunteer_id', $volunteerIds)->delete();
                        
                        foreach ($volunteerIds as $volunteerId) {
                            ServiceScheduleVolunteer::updateOrCreate(
                                [
                                    'schedule_area_id' => $scheduleArea->id,
                                    'volunteer_id' => $volunteerId,
                                ],
                                [
                                    'status' => 'pendente',
                                ]
                            );
                        }
                    }
                } else {
                    // Criar nova área
                    $scheduleArea = ServiceScheduleArea::create([
                        'schedule_id' => $escala->id,
                        'service_area_id' => $areaData['service_area_id'],
                        'required_quantity' => $areaData['required_quantity'],
                        'responsible_id' => $areaData['responsible_id'] ?? null,
                    ]);

                    // Criar voluntários
                    $volunteerIds = $areaData['volunteer_ids'] ?? [];
                    foreach ($volunteerIds as $volunteerId) {
                        ServiceScheduleVolunteer::create([
                            'schedule_area_id' => $scheduleArea->id,
                            'volunteer_id' => $volunteerId,
                            'status' => 'pendente',
                        ]);
                    }
                }
            }

            DB::commit();

            return redirect()->route('voluntarios.escalas.show', $escala)
                ->with('success', 'Escala atualizada com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Erro ao atualizar escala: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceSchedule $escala)
    {
        if ($escala->status === 'publicada') {
            return redirect()->route('voluntarios.escalas.index')
                ->with('error', 'Não é possível excluir uma escala publicada. Cancele-a primeiro.');
        }

        $escala->delete();

        return redirect()->route('voluntarios.escalas.index')
            ->with('success', 'Escala excluída com sucesso!');
    }

    /**
     * Duplicar escala
     */
    public function duplicate(ServiceSchedule $escala)
    {
        DB::beginTransaction();
        try {
            $newSchedule = $escala->replicate();
            $newSchedule->title = $escala->title . ' (Cópia)';
            $newSchedule->status = 'rascunho';
            $newSchedule->save();

            foreach ($escala->areas as $area) {
                $newArea = $area->replicate();
                $newArea->schedule_id = $newSchedule->id;
                $newArea->save();

                foreach ($area->volunteers as $volunteer) {
                    $newVolunteer = $volunteer->replicate();
                    $newVolunteer->schedule_area_id = $newArea->id;
                    $newVolunteer->status = 'pendente';
                    $newVolunteer->save();
                }
            }

            DB::commit();

            return redirect()->route('voluntarios.escalas.edit', $newSchedule)
                ->with('success', 'Escala duplicada com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Erro ao duplicar escala: ' . $e->getMessage());
        }
    }

    /**
     * Cancelar escala
     */
    public function cancel(ServiceSchedule $escala)
    {
        $escala->update(['status' => 'cancelada']);

        return redirect()->route('voluntarios.escalas.show', $escala)
            ->with('success', 'Escala cancelada com sucesso!');
    }

    /**
     * Publicar escala
     */
    public function publish(ServiceSchedule $escala)
    {
        $wasCanceled = $escala->status === 'cancelada';
        $escala->update(['status' => 'publicada']);

        $message = $wasCanceled 
            ? 'Escala republicada com sucesso!' 
            : 'Escala publicada com sucesso!';

        return redirect()->route('voluntarios.escalas.show', $escala)
            ->with('success', $message);
    }

    /**
     * Atualizar status da escala
     */
    public function updateStatus(Request $request, ServiceSchedule $escala)
    {
        $validated = $request->validate([
            'status' => 'required|in:rascunho,publicada,cancelada,concluido',
        ]);

        $oldStatus = $escala->status;
        $escala->update(['status' => $validated['status']]);

        // Se mudou para "concluido", gerar histórico
        if ($validated['status'] === 'concluido' && $oldStatus !== 'concluido') {
            $this->generateServiceHistory($escala);
        }

        $statusLabels = [
            'rascunho' => 'Rascunho',
            'publicada' => 'Publicada',
            'cancelada' => 'Cancelada',
            'concluido' => 'Concluído',
        ];

        return response()->json([
            'success' => true,
            'message' => 'Status atualizado para ' . $statusLabels[$validated['status']] . ' com sucesso!',
            'status' => $validated['status'],
            'status_label' => $statusLabels[$validated['status']],
        ]);
    }

    /**
     * Gerar histórico de serviço quando escala é finalizada
     */
    private function generateServiceHistory(ServiceSchedule $escala)
    {
        $escala->load(['areas.volunteers.volunteer.member', 'areas.serviceArea']);

        foreach ($escala->areas as $scheduleArea) {
            foreach ($scheduleArea->volunteers as $scheduleVolunteer) {
                \App\Models\ServiceHistory::create([
                    'member_id' => $scheduleVolunteer->volunteer->member_id,
                    'volunteer_id' => $scheduleVolunteer->volunteer_id,
                    'service_area_id' => $scheduleArea->service_area_id,
                    'schedule_id' => $escala->id,
                    'date' => $escala->date,
                    'service_type' => $escala->type,
                    'status' => $scheduleVolunteer->status === 'confirmado' ? 'serviu' : 'confirmado_nao_compareceu',
                    'notes' => null,
                ]);
            }
        }
    }

    /**
     * API: Obter voluntários sugeridos para uma área
     */
    public function getSuggestedVolunteers(Request $request)
    {
        $validated = $request->validate([
            'service_area_id' => 'required|exists:service_areas,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
        ]);

        $serviceArea = ServiceArea::findOrFail($validated['service_area_id']);
        
        // Buscar voluntários da área
        $volunteers = Volunteer::whereHas('serviceAreas', function($q) use ($serviceArea) {
            $q->where('service_areas.id', $serviceArea->id);
        })
        ->with(['member'])
        ->where('status', 'ativo')
        ->get();

        // Retornar todos os voluntários como disponíveis
        $suggested = $volunteers->map(function($volunteer) {
            return [
                'id' => $volunteer->id,
                'name' => $volunteer->member->name,
                'available' => true,
                'reason' => '',
            ];
        })->values();

        return response()->json($suggested);
    }

    /**
     * Confirmar voluntário na escala
     */
    public function confirmVolunteer(Request $request, ServiceScheduleVolunteer $volunteer)
    {
        $volunteer->update(['status' => 'confirmado']);

        // Se for requisição AJAX, retorna JSON
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Voluntário confirmado com sucesso!'
            ]);
        }

        // Caso contrário, redireciona de volta com mensagem de sucesso
        $member = $volunteer->volunteer->member;
        return redirect()->route('members.show', ['member' => $member->id])
            ->with('success', 'Presença confirmada com sucesso!');
    }

    /**
     * Remover voluntário da escala
     */
    public function removeVolunteer(ServiceScheduleVolunteer $volunteer)
    {
        $volunteer->delete();

        return response()->json([
            'success' => true,
            'message' => 'Voluntário removido com sucesso!'
        ]);
    }

    /**
     * Substituir voluntário na escala
     */
    public function substituteVolunteer(Request $request, ServiceScheduleVolunteer $volunteer)
    {
        $validated = $request->validate([
            'new_volunteer_id' => 'required|exists:volunteers,id',
        ]);

        // Buscar a área da escala e a escala
        $scheduleArea = $volunteer->scheduleArea;
        $schedule = $scheduleArea->schedule;

        // Verificar se o novo voluntário já está na mesma área
        $existingVolunteer = ServiceScheduleVolunteer::where('schedule_area_id', $scheduleArea->id)
            ->where('volunteer_id', $validated['new_volunteer_id'])
            ->first();

        if ($existingVolunteer) {
            return response()->json([
                'success' => false,
                'message' => 'Este voluntário já está na escala!'
            ], 400);
        }

        // Substituir o voluntário
        $oldVolunteerId = $volunteer->volunteer_id;
        $oldStatus = $volunteer->status;
        
        $volunteer->update([
            'volunteer_id' => $validated['new_volunteer_id'],
            'status' => 'pendente', // Reset status para pendente na substituição
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Voluntário substituído com sucesso!'
        ]);
    }

    /**
     * Gerar PDF da escala
     */
    public function generatePdf(ServiceSchedule $escala)
    {
        // Verificar se a escala está publicada
        if ($escala->status !== 'publicada') {
            return redirect()->route('voluntarios.escalas.show', $escala)
                ->with('error', 'Apenas escalas publicadas podem ser exportadas em PDF.');
        }

        // Carregar relacionamentos necessários
        $escala->load([
            'areas.serviceArea',
            'areas.responsible',
            'areas.volunteers.volunteer.member',
            'event'
        ]);

        // Buscar logo da igreja - usar logo branca específica
        $logoBase64 = null;
        $logoPath = null;
        $logoFileName = 'LOG SS branca.png';
        $logoPublicPath = public_path("img/img/{$logoFileName}");
        
        // Verificar se a logo branca existe no caminho especificado
        if (file_exists($logoPublicPath)) {
            $logoPath = $logoPublicPath;
            // Converter para base64 para garantir compatibilidade com DOMPDF
            $imageData = file_get_contents($logoPublicPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($imageData);
        } else {
            // Fallback: tentar logo em storage
            $logoExists = Storage::disk('public')->exists('church/logo.png');
            if ($logoExists) {
                $logoPath = storage_path('app/public/church/logo.png');
                $imageData = file_get_contents($logoPath);
                $logoBase64 = 'data:image/png;base64,' . base64_encode($imageData);
            } else {
                // Tentar outros formatos
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

        // Nome da igreja (pode ser configurável no futuro)
        $churchName = 'ADELSS';

        // Gerar nome do arquivo
        $fileName = 'escala-' . Str::slug($escala->title) . '-' . $escala->date->format('d-m-Y') . '.pdf';

        // Renderizar view do PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('service-schedules.pdf', [
            'escala' => $escala,
            'logoPath' => $logoPath,
            'logoBase64' => $logoBase64,
            'churchName' => $churchName,
            'generatedAt' => now(),
        ])->setPaper('A4', 'portrait')
          ->setOption('enable-local-file-access', true);

        // Retornar download
        return $pdf->download($fileName);
    }
}
