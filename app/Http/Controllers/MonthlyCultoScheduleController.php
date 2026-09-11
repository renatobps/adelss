<?php

namespace App\Http\Controllers;

use App\Models\MonthlyCultoSchedule;
use App\Models\ServiceSchedule;
use App\Models\ConfiguracaoMensagem;
use App\Models\Event;
use App\Models\Member;
use App\Models\Volunteer;
use App\Models\ServiceArea;
use App\Services\NotificacaoService;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;

class MonthlyCultoScheduleController extends Controller
{
    /**
     * Lista mensal de escalas de cultos
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', ServiceSchedule::class);
        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);
        $status = $request->get('status');

        $query = MonthlyCultoSchedule::with(['event', 'serviceAreaVolunteers.member'])
            ->byMonthYear($month, $year);

        // Filtro por status
        if ($status) {
            $query->where('status', $status);
        }

        $schedules = $query->orderBy('event_id')->get();

        $serviceAreas = ServiceArea::where('status', 'ativo')->orderBy('name')->get();
        $cultos = $this->getCultosDoMes((int) $month, (int) $year);
        $scheduleBuilder = $this->buildManualSchedulePayload($serviceAreas, (int) $month, (int) $year);

        $templates = ConfiguracaoMensagem::where('ativo', true)
            ->orderBy('tipo_notificacao')
            ->get(['id', 'tipo_notificacao', 'template']);

        return view('monthly-culto-schedules.index', compact(
            'schedules',
            'month',
            'year',
            'serviceAreas',
            'cultos',
            'scheduleBuilder',
            'templates'
        ));
    }

    /**
     * Mostra formulário para selecionar mês e listar cultos
     */
    public function create(Request $request)
    {
        $this->authorize('create', ServiceSchedule::class);
        $month = $request->get('month', Carbon::now()->month);
        $year = $request->get('year', Carbon::now()->year);

        // Buscar cultos do mês (eventos que contenham "culto" no título ou categoria)
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        $cultos = Event::whereBetween('start_date', [$startDate, $endDate])
            ->where(function($query) {
                $query->where('title', 'like', '%culto%')
                      ->orWhere('title', 'like', '%Culto%')
                      ->orWhereHas('category', function($q) {
                          $q->where('name', 'like', '%culto%')
                            ->orWhere('name', 'like', '%Culto%');
                      });
            })
            ->orderBy('start_date')
            ->get();

        // Buscar escalas já cadastradas para este mês
        $existingSchedules = MonthlyCultoSchedule::byMonthYear($month, $year)
            ->pluck('event_id')
            ->toArray();

        // Buscar todas as áreas de serviço ativas
        $serviceAreas = ServiceArea::where('status', 'ativo')->orderBy('name')->get();

        // Verificar se existe área de serviço para Preletor ou Dirigente
        $preletorArea = ServiceArea::where('status', 'ativo')
            ->where(function($query) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%preletor%'])
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%pregador%']);
            })
            ->first();
        
        $dirigenteArea = ServiceArea::where('status', 'ativo')
            ->where(function($query) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%dirigente%'])
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%direção%'])
                      ->orWhereRaw('LOWER(name) LIKE ?', ['%direcao%']);
            })
            ->first();

        // Filtrar áreas de serviço para não incluir Preletor e Dirigente se já existirem como áreas
        $filteredServiceAreas = $serviceAreas->filter(function($area) use ($preletorArea, $dirigenteArea) {
            // Se existe área de Preletor, não incluir na lista (será tratada separadamente se necessário)
            if ($preletorArea && $area->id == $preletorArea->id) {
                return false;
            }
            // Se existe área de Dirigente, não incluir na lista (será tratada separadamente se necessário)
            if ($dirigenteArea && $area->id == $dirigenteArea->id) {
                return false;
            }
            return true;
        });

        // Para cada área de serviço, buscar voluntários que têm essa área cadastrada
        $volunteersByArea = [];
        foreach ($serviceAreas as $area) {
            $volunteers = Volunteer::where('status', 'ativo')
                ->whereHas('serviceAreas', function($query) use ($area) {
                    $query->where('service_areas.id', $area->id);
                })
                ->with('member')
                ->orderBy('id')
                ->get();
            
            if ($volunteers->count() > 0) {
                $volunteersByArea[$area->id] = $volunteers->map(function($volunteer) {
                    return [
                        'id' => $volunteer->id,
                        'name' => $volunteer->member->name ?? 'Sem nome',
                        'member_id' => $volunteer->member_id,
                    ];
                });
            }
        }

        // Não passar preletores e dirigentes separados - tudo será tratado pelas áreas de serviço
        return view('monthly-culto-schedules.create', compact('cultos', 'month', 'year', 'existingSchedules', 'serviceAreas', 'volunteersByArea'));
    }

    /**
     * Salva escala mensal de culto
     */
    public function store(Request $request)
    {
        $this->authorize('create', ServiceSchedule::class);
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
            'service_areas' => 'nullable|array',
            'service_areas.*' => 'nullable|array',
            'service_areas.*.*' => 'exists:volunteers,id',
        ], [
            'event_id.required' => 'Selecione um culto.',
            'event_id.exists' => 'O culto selecionado não existe.',
            'month.required' => 'O mês é obrigatório.',
            'year.required' => 'O ano é obrigatório.',
            'service_areas.array' => 'As áreas de serviço devem ser uma lista válida.',
        ]);

        // Verificar se já existe escala para este culto no mês/ano
        $existing = MonthlyCultoSchedule::where('event_id', $validated['event_id'])
            ->where('month', $validated['month'])
            ->where('year', $validated['year'])
            ->first();

        if ($existing) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Já existe uma escala cadastrada para este culto neste mês.');
        }

        if ($this->hasDuplicateVolunteersInPayload($validated['service_areas'] ?? [])) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Regra aplicada: no mesmo culto, o mesmo voluntário não pode ser repetido em mais de uma área.');
        }

        $schedule = MonthlyCultoSchedule::create([
            'event_id' => $validated['event_id'],
            'month' => $validated['month'],
            'year' => $validated['year'],
            'status' => 'rascunho',
        ]);

        // Sincronizar áreas de serviço
        if (isset($validated['service_areas']) && is_array($validated['service_areas'])) {
            foreach ($validated['service_areas'] as $serviceAreaId => $volunteerIds) {
                if (is_array($volunteerIds) && count($volunteerIds) > 0) {
                    foreach ($volunteerIds as $volunteerId) {
                        $schedule->serviceAreaVolunteers()->syncWithoutDetaching([
                            $volunteerId => [
                                'service_area_id' => $serviceAreaId,
                                'status' => 'pendente'
                            ]
                        ]);
                    }
                }
            }
        }

        return redirect()->route('voluntarios.escalas-mensais.index', ['month' => $validated['month'], 'year' => $validated['year']])
            ->with('success', 'Escala mensal cadastrada com sucesso!');
    }

    /**
     * Mostra formulário de edição
     */
    public function edit(MonthlyCultoSchedule $escala)
    {
        $this->authorize('update', new ServiceSchedule());
        $escala->load(['event', 'serviceAreaVolunteers']);

        // Buscar todas as áreas de serviço ativas
        $serviceAreas = ServiceArea::where('status', 'ativo')->orderBy('name')->get();

        // Para cada área de serviço, buscar voluntários que têm essa área cadastrada
        $volunteersByArea = [];
        foreach ($serviceAreas as $area) {
            $volunteers = Volunteer::where('status', 'ativo')
                ->whereHas('serviceAreas', function($query) use ($area) {
                    $query->where('service_areas.id', $area->id);
                })
                ->with('member')
                ->orderBy('id')
                ->get();
            
            if ($volunteers->count() > 0) {
                $volunteersByArea[$area->id] = $volunteers->map(function($volunteer) {
                    return [
                        'id' => $volunteer->id,
                        'name' => $volunteer->member->name ?? 'Sem nome',
                        'member_id' => $volunteer->member_id,
                    ];
                });
            }
        }

        // Buscar voluntários já selecionados por área de serviço
        $selectedVolunteersByArea = [];
        foreach ($serviceAreas as $area) {
            $selectedVolunteersByArea[$area->id] = $escala->getVolunteersByServiceArea($area->id)->pluck('id')->toArray();
        }

        return view('monthly-culto-schedules.edit', compact('escala', 'serviceAreas', 'volunteersByArea', 'selectedVolunteersByArea'));
    }

    /**
     * Atualiza escala mensal
     */
    public function update(Request $request, MonthlyCultoSchedule $escala)
    {
        $this->authorize('update', new ServiceSchedule());
        $validated = $request->validate([
            'service_areas' => 'nullable|array',
            'service_areas.*' => 'nullable|array',
            'service_areas.*.*' => 'exists:volunteers,id',
        ], [
            'service_areas.array' => 'As áreas de serviço devem ser uma lista válida.',
        ]);

        if ($this->hasDuplicateVolunteersInPayload($validated['service_areas'] ?? [])) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Regra aplicada: no mesmo culto, o mesmo voluntário não pode ser repetido em mais de uma área.');
        }

        // Remover todas as áreas de serviço existentes e adicionar as novas
        $escala->serviceAreaVolunteers()->detach();

        // Sincronizar áreas de serviço
        if (isset($validated['service_areas']) && is_array($validated['service_areas'])) {
            foreach ($validated['service_areas'] as $serviceAreaId => $volunteerIds) {
                if (is_array($volunteerIds) && count($volunteerIds) > 0) {
                    foreach ($volunteerIds as $volunteerId) {
                        $escala->serviceAreaVolunteers()->attach($volunteerId, [
                            'service_area_id' => $serviceAreaId,
                            'status' => 'pendente'
                        ]);
                    }
                }
            }
        }

        $preletorArea = $this->findPreletorArea();
        if ($preletorArea) {
            $hasPreletorVolunteer = collect($validated['service_areas'][$preletorArea->id] ?? [])
                ->filter()
                ->isNotEmpty();
            if ($hasPreletorVolunteer) {
                $escala->update(['guest_preletor_name' => null]);
            }
        }

        return redirect()->route('voluntarios.escalas-mensais.index', ['month' => $escala->month, 'year' => $escala->year])
            ->with('success', 'Escala mensal atualizada com sucesso!');
    }

    /**
     * Remove escala mensal
     */
    public function destroy(MonthlyCultoSchedule $escala)
    {
        $this->authorize('delete', new ServiceSchedule());
        $month = $escala->month;
        $year = $escala->year;
        
        if ($escala->status === 'publicada') {
            return redirect()->route('voluntarios.escalas-mensais.index', ['month' => $month, 'year' => $year])
                ->with('error', 'Não é possível excluir uma escala publicada. Cancele-a primeiro.');
        }
        
        $escala->delete();

        return redirect()->route('voluntarios.escalas-mensais.index', ['month' => $month, 'year' => $year])
            ->with('success', 'Escala mensal removida com sucesso!');
    }

    /**
     * Visualizar escala mensal
     */
    public function show(MonthlyCultoSchedule $escala)
    {
        $this->authorize('view', new ServiceSchedule());
        $escala->load(['event', 'serviceAreaVolunteers.member']);
        
        // Buscar todas as áreas de serviço para exibição
        $serviceAreas = ServiceArea::where('status', 'ativo')
            ->with('leader')
            ->orderBy('name')
            ->get();
        
        // Organizar voluntários por área com status
        $volunteersByArea = [];
        foreach ($serviceAreas as $area) {
            $volunteers = $escala->serviceAreaVolunteers()
                ->wherePivot('service_area_id', $area->id)
                ->get();
            
            $volunteersByArea[$area->id] = $volunteers;
        }

        $templates = ConfiguracaoMensagem::where('ativo', true)
            ->orderBy('tipo_notificacao')
            ->get(['id', 'tipo_notificacao', 'template']);

        return view('monthly-culto-schedules.show', compact('escala', 'serviceAreas', 'volunteersByArea', 'templates'));
    }

    /**
     * Atualizar status da escala
     */
    public function updateStatus(Request $request, MonthlyCultoSchedule $escala)
    {
        $this->authorize('update', new ServiceSchedule());
        $validated = $request->validate([
            'status' => 'required|in:rascunho,publicada,cancelada,concluido',
        ]);

        $escala->update(['status' => $validated['status']]);

        $statusLabels = [
            'rascunho' => 'Rascunho',
            'publicada' => 'Publicada',
            'cancelada' => 'Cancelada',
            'concluido' => 'Concluído',
        ];

        return response()->json([
            'success' => true,
            'message' => 'Status alterado para ' . $statusLabels[$validated['status']] . '!',
            'status' => $validated['status'],
        ]);
    }

    /**
     * Publicar escala
     */
    public function publish(MonthlyCultoSchedule $escala)
    {
        $this->authorize('update', new ServiceSchedule());
        $wasCanceled = $escala->status === 'cancelada';
        $escala->update(['status' => 'publicada']);

        $message = $wasCanceled 
            ? 'Escala republicada com sucesso!' 
            : 'Escala publicada com sucesso!';

        return redirect()->route('voluntarios.escalas-mensais.show', $escala)
            ->with('success', $message);
    }

    /**
     * Cancelar escala
     */
    public function cancel(MonthlyCultoSchedule $escala)
    {
        $this->authorize('update', new ServiceSchedule());
        $escala->update(['status' => 'cancelada']);

        return redirect()->route('voluntarios.escalas-mensais.show', $escala)
            ->with('success', 'Escala cancelada com sucesso!');
    }

    /**
     * Gerar PDF da escala usando DOMPDF
     */
    public function generatePdf(MonthlyCultoSchedule $escala)
    {
        $this->authorize('view', new ServiceSchedule());
        // Verificar se a escala está publicada
        if ($escala->status !== 'publicada') {
            return redirect()->route('voluntarios.escalas-mensais.show', $escala)
                ->with('error', 'Apenas escalas publicadas podem ser exportadas em PDF.');
        }

        // Carregar relacionamentos necessários
        $escala->load(['event', 'serviceAreaVolunteers.member']);
        
        // Buscar todas as áreas de serviço
        $serviceAreas = ServiceArea::where('status', 'ativo')->orderBy('name')->get();
        
        // Organizar voluntários por área
        $volunteersByArea = [];
        foreach ($serviceAreas as $area) {
            $volunteers = $escala->getVolunteersByServiceArea($area->id);
            $volunteersByArea[$area->id] = $volunteers;
        }

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
        $fileName = 'escala-mensal-' . Str::slug($escala->event->title) . '-' . $escala->month . '-' . $escala->year . '.pdf';

        // Renderizar view do PDF
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.escalas.escala-mensal', [
            'escala' => $escala,
            'serviceAreas' => $serviceAreas,
            'volunteersByArea' => $volunteersByArea,
            'logoPath' => $logoPath,
            'logoBase64' => $logoBase64,
            'churchName' => $churchName,
            'generatedAt' => now(),
        ])->setPaper('A4', 'portrait')
          ->setOption('enable-local-file-access', true);

        return $pdf->download($fileName);
    }

    /**
     * Confirmar voluntário na escala
     */
    public function confirmVolunteer(Request $request, $pivotId)
    {
        $this->authorize('update', new ServiceSchedule());
        $pivot = \DB::table('monthly_culto_service_areas')->where('id', $pivotId)->first();
        
        if (!$pivot) {
            return response()->json([
                'success' => false,
                'message' => 'Voluntário não encontrado na escala.'
            ], 404);
        }

        \DB::table('monthly_culto_service_areas')
            ->where('id', $pivotId)
            ->update(['status' => 'confirmado']);

        return response()->json([
            'success' => true,
            'message' => 'Voluntário confirmado com sucesso!'
        ]);
    }

    /**
     * Substituir voluntário na escala
     */
    public function substituteVolunteer(Request $request, $pivotId)
    {
        $this->authorize('update', new ServiceSchedule());
        $validated = $request->validate([
            'new_volunteer_id' => 'required|exists:volunteers,id',
        ]);

        $pivot = \DB::table('monthly_culto_service_areas')->where('id', $pivotId)->first();
        
        if (!$pivot) {
            return response()->json([
                'success' => false,
                'message' => 'Voluntário não encontrado na escala.'
            ], 404);
        }

        // Verificar se o novo voluntário já está em qualquer área do mesmo culto
        $existing = \DB::table('monthly_culto_service_areas')
            ->where('monthly_culto_schedule_id', $pivot->monthly_culto_schedule_id)
            ->where('volunteer_id', $validated['new_volunteer_id'])
            ->where('id', '!=', $pivotId)
            ->first();

        if ($existing) {
            return response()->json([
                'success' => false,
                'message' => 'Este voluntário já está na escala!'
            ], 400);
        }

        // Substituir o voluntário
        \DB::table('monthly_culto_service_areas')
            ->where('id', $pivotId)
            ->update([
                'volunteer_id' => $validated['new_volunteer_id'],
                'status' => 'pendente',
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Voluntário substituído com sucesso!'
        ]);
    }

    /**
     * Remover voluntário da escala
     */
    public function removeVolunteer($pivotId)
    {
        $this->authorize('update', new ServiceSchedule());
        $pivot = \DB::table('monthly_culto_service_areas')->where('id', $pivotId)->first();
        
        if (!$pivot) {
            return response()->json([
                'success' => false,
                'message' => 'Voluntário não encontrado na escala.'
            ], 404);
        }

        \DB::table('monthly_culto_service_areas')->where('id', $pivotId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Voluntário removido com sucesso!'
        ]);
    }

    /**
     * Buscar voluntários disponíveis para uma área de serviço
     */
    public function getAvailableVolunteers(Request $request)
    {
        $this->authorize('viewAny', ServiceSchedule::class);
        $serviceAreaId = $request->get('service_area_id');
        $scheduleId = $request->get('schedule_id');
        
        if (!$serviceAreaId) {
            return response()->json([
                'success' => false,
                'message' => 'Área de serviço não informada'
            ], 400);
        }

        $query = Volunteer::where('status', 'ativo')
            ->whereHas('serviceAreas', function($query) use ($serviceAreaId) {
                $query->where('service_areas.id', $serviceAreaId);
            })
            ->with('member')
            ->orderBy('id');

        if ($scheduleId) {
            $assignedVolunteerIds = \DB::table('monthly_culto_service_areas')
                ->where('monthly_culto_schedule_id', $scheduleId)
                ->pluck('volunteer_id')
                ->toArray();

            if (!empty($assignedVolunteerIds)) {
                $query->whereNotIn('id', $assignedVolunteerIds);
            }
        }

        $volunteers = $query->get()
            ->map(function($volunteer) {
                return [
                    'id' => $volunteer->id,
                    'name' => $volunteer->member->name ?? 'Sem nome',
                ];
            });

        return response()->json([
            'success' => true,
            'volunteers' => $volunteers
        ]);
    }

    public function addVolunteer(Request $request, MonthlyCultoSchedule $escala)
    {
        $this->authorize('update', new ServiceSchedule());
        $validated = $request->validate([
            'service_area_id' => 'required|exists:service_areas,id',
            'volunteer_id' => 'required|exists:volunteers,id',
        ]);

        $alreadyAssigned = \DB::table('monthly_culto_service_areas')
            ->where('monthly_culto_schedule_id', $escala->id)
            ->where('volunteer_id', $validated['volunteer_id'])
            ->exists();

        if ($alreadyAssigned) {
            return response()->json([
                'success' => false,
                'message' => 'Regra aplicada: no mesmo culto, o voluntário não pode ser repetido.',
            ], 422);
        }

        $belongsToArea = Volunteer::where('id', $validated['volunteer_id'])
            ->where('status', 'ativo')
            ->whereHas('serviceAreas', function ($query) use ($validated) {
                $query->where('service_areas.id', $validated['service_area_id']);
            })
            ->exists();

        if (!$belongsToArea) {
            return response()->json([
                'success' => false,
                'message' => 'O voluntário selecionado não pertence à área de serviço.',
            ], 422);
        }

        $escala->serviceAreaVolunteers()->attach($validated['volunteer_id'], [
            'service_area_id' => $validated['service_area_id'],
            'status' => 'pendente',
        ]);

        $preletorArea = $this->findPreletorArea();
        if ($preletorArea && (int) $validated['service_area_id'] === (int) $preletorArea->id) {
            $escala->update(['guest_preletor_name' => null]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Voluntário adicionado manualmente com sucesso!',
        ]);
    }

    public function notifyVolunteer(Request $request, NotificacaoService $notificacaoService)
    {
        $this->authorize('update', new ServiceSchedule());
        $validated = $request->validate([
            'pivot_id' => 'required|integer|exists:monthly_culto_service_areas,id',
            'template_id' => 'nullable|integer|exists:configuracoes_mensagens,id',
            'mensagem' => 'nullable|string|max:4096',
            'arquivo' => 'nullable|file|max:20480',
            'enviar_pdf' => 'nullable|boolean',
        ]);

        $pivot = DB::table('monthly_culto_service_areas')->where('id', $validated['pivot_id'])->first();
        if (!$pivot) {
            return back()->with('error', 'Registro de escala não encontrado.');
        }

        $schedule = MonthlyCultoSchedule::with('event')->find($pivot->monthly_culto_schedule_id);
        $serviceArea = ServiceArea::find($pivot->service_area_id);
        $volunteer = Volunteer::with('member')->find($pivot->volunteer_id);

        if (!$schedule || !$schedule->event || !$volunteer || !$volunteer->member) {
            return back()->with('error', 'Dados da escala incompletos para notificação.');
        }

        $member = $volunteer->member;
        if (empty($member->phone)) {
            return back()->with('error', 'A pessoa escalada não possui telefone vinculado.');
        }

        $variables = [
            '{nome}' => $member->name,
            '{culto}' => $schedule->event->title ?? '',
            '{dia_culto}' => optional($schedule->event->start_date)->format('d/m/Y') ?? '',
            '{hora_culto}' => optional($schedule->event->start_date)->format('H:i') ?? '',
            '{area_servico}' => $serviceArea->name ?? '',
            '{local_servico}' => $schedule->event->location ?? 'Não informado',
        ];

        $templateMessage = '';
        if (!empty($validated['template_id'])) {
            $template = ConfiguracaoMensagem::find($validated['template_id']);
            if ($template) {
                $templateMessage = ConfiguracaoMensagem::aplicarVariaveis($template->template, $variables);
            }
        }

        $rawMessage = trim((string) ($validated['mensagem'] ?? ''));
        $message = $rawMessage !== ''
            ? ConfiguracaoMensagem::aplicarVariaveis($rawMessage, $variables)
            : $templateMessage;

        $sendPdf = (bool) $request->boolean('enviar_pdf');
        $hasFile = $request->hasFile('arquivo');

        if ($message === '' && !$hasFile && !$sendPdf) {
            return back()->with('error', 'Digite uma mensagem ou selecione um template para enviar a notificação.');
        }

        $errors = [];

        if ($hasFile) {
            $result = $notificacaoService->enviarMidiaParaMembros(
                collect([$member]),
                $request->file('arquivo'),
                null,
                $message
            );

            if (($result['enviadas'] ?? 0) <= 0) {
                $errors[] = 'Falha ao enviar arquivo de mídia.';
            }
        } elseif ($message !== '') {
            $result = $notificacaoService->enviarParaMembro($member, $message);
            if (!($result['success'] ?? false)) {
                $errors[] = $result['error'] ?? 'Falha ao enviar mensagem.';
            }
        }

        if ($sendPdf) {
            $tempPdfPath = null;
            try {
                $pdfOutput = $this->buildMonthlySchedulePdfOutput($schedule);
                $tempDirectory = storage_path('app/tmp');
                if (!is_dir($tempDirectory)) {
                    mkdir($tempDirectory, 0775, true);
                }

                $tempPdfPath = $tempDirectory . DIRECTORY_SEPARATOR . 'escala-mensal-' . $schedule->id . '-' . time() . '.pdf';
                file_put_contents($tempPdfPath, $pdfOutput);

                $pdfFile = new UploadedFile(
                    $tempPdfPath,
                    'escala-mensal-' . $schedule->id . '.pdf',
                    'application/pdf',
                    null,
                    true
                );

                $resultPdf = $notificacaoService->enviarMidiaParaMembros(
                    collect([$member]),
                    $pdfFile,
                    'document',
                    'PDF da escala do dia.'
                );

                if (($resultPdf['enviadas'] ?? 0) <= 0) {
                    $errors[] = 'Falha ao enviar PDF da escala.';
                }
            } catch (\Throwable $exception) {
                $errors[] = 'Falha ao gerar/enviar PDF da escala.';
            } finally {
                if ($tempPdfPath && file_exists($tempPdfPath)) {
                    @unlink($tempPdfPath);
                }
            }
        }

        if (!empty($errors)) {
            return back()->with('error', implode(' ', $errors));
        }

        return back()->with('success', 'Notificação enviada com sucesso para a pessoa escalada.');
    }

    public function notifyAllVolunteers(Request $request, MonthlyCultoSchedule $escala, NotificacaoService $notificacaoService)
    {
        $this->authorize('update', new ServiceSchedule());
        $validated = $request->validate([
            'template_id' => 'nullable|integer|exists:configuracoes_mensagens,id',
            'mensagem' => 'nullable|string|max:4096',
            'arquivo' => 'nullable|file|max:20480',
            'enviar_pdf' => 'nullable|boolean',
        ]);

        $escala->loadMissing(['event', 'serviceAreaVolunteers.member']);

        $assignedVolunteers = $escala->serviceAreaVolunteers;
        if ($assignedVolunteers->isEmpty()) {
            return back()->with('error', 'Não há voluntários escalados para notificar.');
        }

        $serviceAreaNames = ServiceArea::whereIn('id', $assignedVolunteers->pluck('pivot.service_area_id')->unique()->all())
            ->pluck('name', 'id');

        $templateText = '';
        if (!empty($validated['template_id'])) {
            $template = ConfiguracaoMensagem::find($validated['template_id']);
            if ($template) {
                $templateText = $template->template;
            }
        }

        $rawMessage = trim((string) ($validated['mensagem'] ?? ''));
        $baseMessage = $rawMessage !== '' ? $rawMessage : $templateText;

        $sendPdf = (bool) $request->boolean('enviar_pdf');
        $hasFile = $request->hasFile('arquivo');

        if ($baseMessage === '' && !$hasFile && !$sendPdf) {
            return back()->with('error', 'Digite uma mensagem ou selecione um template para enviar a notificação.');
        }

        $enviadas = 0;
        $erros = 0;
        $semTelefone = 0;

        $tempPdfPath = null;
        $pdfFile = null;
        if ($sendPdf) {
            try {
                $pdfOutput = $this->buildMonthlySchedulePdfOutput($escala);
                $tempDirectory = storage_path('app/tmp');
                if (!is_dir($tempDirectory)) {
                    mkdir($tempDirectory, 0775, true);
                }

                $tempPdfPath = $tempDirectory . DIRECTORY_SEPARATOR . 'escala-mensal-lote-' . $escala->id . '-' . time() . '.pdf';
                file_put_contents($tempPdfPath, $pdfOutput);

                $pdfFile = new UploadedFile(
                    $tempPdfPath,
                    'escala-mensal-' . $escala->id . '.pdf',
                    'application/pdf',
                    null,
                    true
                );
            } catch (\Throwable $exception) {
                return back()->with('error', 'Falha ao gerar PDF da escala para envio.');
            }
        }

        try {
            foreach ($assignedVolunteers as $volunteer) {
                $member = $volunteer->member;
                if (!$member || empty($member->phone)) {
                    $semTelefone++;
                    continue;
                }

                $areaName = $serviceAreaNames[$volunteer->pivot->service_area_id] ?? '';
                $variables = [
                    '{nome}' => $member->name,
                    '{culto}' => $escala->event->title ?? '',
                    '{dia_culto}' => optional($escala->event->start_date)->format('d/m/Y') ?? '',
                    '{hora_culto}' => optional($escala->event->start_date)->format('H:i') ?? '',
                    '{area_servico}' => $areaName,
                    '{local_servico}' => $escala->event->location ?? 'Não informado',
                ];

                $message = $baseMessage !== '' ? ConfiguracaoMensagem::aplicarVariaveis($baseMessage, $variables) : '';

                if ($hasFile) {
                    $resultMidia = $notificacaoService->enviarMidiaParaMembros(
                        collect([$member]),
                        $request->file('arquivo'),
                        null,
                        $message
                    );
                    if (($resultMidia['enviadas'] ?? 0) > 0) {
                        $enviadas++;
                    } else {
                        $erros++;
                    }
                } elseif ($message !== '') {
                    $resultText = $notificacaoService->enviarParaMembro($member, $message);
                    if ($resultText['success'] ?? false) {
                        $enviadas++;
                    } else {
                        $erros++;
                    }
                }

                if ($pdfFile) {
                    $resultPdf = $notificacaoService->enviarMidiaParaMembros(
                        collect([$member]),
                        $pdfFile,
                        'document',
                        'PDF da escala do dia.'
                    );
                    if (($resultPdf['enviadas'] ?? 0) > 0) {
                        $enviadas++;
                    } else {
                        $erros++;
                    }
                }
            }
        } finally {
            if ($tempPdfPath && file_exists($tempPdfPath)) {
                @unlink($tempPdfPath);
            }
        }

        return back()->with(
            'success',
            "Notificação em lote concluída. Enviadas: {$enviadas}. Erros: {$erros}. Sem telefone: {$semTelefone}."
        );
    }

    /**
     * Cadastro manual mensal por área de serviço
     */
    public function storeManualArea(Request $request)
    {
        $this->authorize('create', ServiceSchedule::class);
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'service_area_id' => 'required|exists:service_areas,id',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
        ], [
            'service_area_id.required' => 'Selecione a área de serviço.',
            'service_area_id.exists' => 'A área de serviço selecionada não existe.',
        ]);

        $month = (int) $request->input('month', Carbon::now()->month);
        $year = (int) $request->input('year', Carbon::now()->year);
        $serviceAreaId = (int) $request->input('service_area_id');

        $redirect = function (string $type, string $message) use ($month, $year, $serviceAreaId) {
            return redirect()->route('voluntarios.escalas-mensais.index', compact('month', 'year'))
                ->with($type, $message)
                ->withInput()
                ->with('open_add_schedule_modal', true)
                ->with('add_schedule_area_id', $serviceAreaId);
        };

        if ($validator->fails()) {
            return redirect()->route('voluntarios.escalas-mensais.index', compact('month', 'year'))
                ->withErrors($validator)
                ->withInput()
                ->with('open_add_schedule_modal', true)
                ->with('add_schedule_area_id', $serviceAreaId)
                ->with('error', $validator->errors()->first());
        }

        $validated = $validator->validated();
        $month = (int) $validated['month'];
        $year = (int) $validated['year'];
        $serviceAreaId = (int) $validated['service_area_id'];

        $serviceArea = ServiceArea::where('status', 'ativo')->find($serviceAreaId);
        if (!$serviceArea) {
            return $redirect('error', 'Área de serviço não encontrada ou inativa.');
        }

        $isPreletor = $this->isPreletorServiceArea($serviceArea);
        $isLimpeza = $this->isLimpezaServiceArea($serviceArea);

        $assignmentIdRule = $isLimpeza ? 'nullable|exists:members,id' : 'nullable|exists:volunteers,id';
        $assignmentValidator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'assignments' => 'nullable|array',
            'assignments.*' => 'nullable|array',
            'assignments.*.*' => $assignmentIdRule,
            'guests' => 'nullable|array',
            'guests.*' => 'nullable|string|max:150',
        ], [
            'assignments.*.*.exists' => $isLimpeza
                ? 'Um dos membros selecionados não existe.'
                : 'Um dos voluntários selecionados não existe.',
            'guests.*.max' => 'O nome do convidado deve ter no máximo 150 caracteres.',
        ]);

        if ($assignmentValidator->fails()) {
            return $redirect('error', $assignmentValidator->errors()->first());
        }

        $cultosDoMes = $this->getCultosDoMes($month, $year);
        if ($isLimpeza) {
            $cultosDoMes = $cultosDoMes->filter(function ($event) {
                return optional($event->start_date)->dayOfWeek === Carbon::SUNDAY;
            })->values();
        }

        $allowedEventIds = $cultosDoMes->pluck('id')->map(fn ($id) => (int) $id)->all();

        $areaVolunteerIds = $isLimpeza
            ? Member::where('status', Member::STATUS_ATIVO)->pluck('id')->map(fn ($id) => (int) $id)
            : Volunteer::where('status', 'ativo')
                ->whereHas('serviceAreas', function ($query) use ($serviceArea) {
                    $query->where('service_areas.id', $serviceArea->id);
                })
                ->pluck('id')
                ->map(fn ($id) => (int) $id);

        $assignmentData = $assignmentValidator->validated();
        $volunteerAssignments = collect($assignmentData['assignments'] ?? []);
        $guestAssignments = collect($assignmentData['guests'] ?? []);
        $payload = [];

        foreach ($volunteerAssignments->keys()->merge($guestAssignments->keys())->unique() as $eventKey) {
            $eventId = (int) $eventKey;
            $rawVolunteers = $volunteerAssignments->get($eventKey) ?? $volunteerAssignments->get($eventId) ?? [];
            $volunteerIds = collect(is_array($rawVolunteers) ? $rawVolunteers : [$rawVolunteers])
                ->filter(fn ($id) => filled($id))
                ->map(fn ($id) => (int) $id)
                ->values();

            $guestName = $isPreletor
                ? trim((string) ($guestAssignments->get($eventKey) ?? $guestAssignments->get($eventId) ?? ''))
                : '';

            if ($volunteerIds->isEmpty() && $guestName === '') {
                continue;
            }

            if ($volunteerIds->count() !== $volunteerIds->unique()->count()) {
                return $redirect('error', 'No mesmo culto, o mesmo voluntário não pode ser escalado mais de uma vez na mesma área.');
            }

            $payload[$eventId] = [
                'volunteer_ids' => $volunteerIds->all(),
                'guest_name' => $guestName,
            ];
        }

        if ($payload === []) {
            return $redirect('error', 'Preencha ao menos um culto com voluntário' . ($isPreletor ? ' ou nome de convidado' : '') . '.');
        }

        if (collect(array_keys($payload))->diff($allowedEventIds)->isNotEmpty()) {
            return $redirect(
                'error',
                $isLimpeza
                    ? 'A escala de limpeza é somente para os cultos de domingo.'
                    : 'Um ou mais cultos não pertencem à agenda do mês (quarta e domingo).'
            );
        }

        $selectedVolunteerIds = collect($payload)->pluck('volunteer_ids')->flatten();
        if ($selectedVolunteerIds->diff($areaVolunteerIds)->isNotEmpty()) {
            return $redirect(
                'error',
                $isLimpeza
                    ? 'Um ou mais membros selecionados não estão ativos.'
                    : "Um ou mais voluntários não pertencem à área {$serviceArea->name}."
            );
        }

        $saved = 0;

        try {
            DB::transaction(function () use ($payload, $serviceArea, $isPreletor, $isLimpeza, $month, $year, &$saved) {
                foreach ($payload as $eventId => $assignment) {
                    $schedule = MonthlyCultoSchedule::firstOrCreate(
                        [
                            'event_id' => $eventId,
                            'month' => $month,
                            'year' => $year,
                        ],
                        [
                            'status' => 'rascunho',
                        ]
                    );

                    $volunteerIds = $isLimpeza
                        ? $this->volunteerIdsForLimpezaMembers($assignment['volunteer_ids'], $serviceArea)
                        : $assignment['volunteer_ids'];

                    if (! $isLimpeza) {
                        foreach ($volunteerIds as $volunteerId) {
                            $conflict = DB::table('monthly_culto_service_areas as pivot')
                                ->join('service_areas as areas', 'areas.id', '=', 'pivot.service_area_id')
                                ->leftJoin('volunteers as volunteers', 'volunteers.id', '=', 'pivot.volunteer_id')
                                ->leftJoin('members as members', 'members.id', '=', 'volunteers.member_id')
                                ->where('pivot.monthly_culto_schedule_id', $schedule->id)
                                ->where('pivot.volunteer_id', $volunteerId)
                                ->where('pivot.service_area_id', '!=', $serviceArea->id)
                                ->select('areas.name as area_name', 'members.name as volunteer_name')
                                ->first();

                            if ($conflict) {
                                $event = Event::find($eventId);
                                $dateLabel = optional($event?->start_date)->format('d/m/Y') ?? (string) $eventId;
                                $volunteerName = $conflict->volunteer_name ?: 'O voluntário';
                                throw new \RuntimeException("No culto de {$dateLabel}, {$volunteerName} já está escalado em {$conflict->area_name}.");
                            }
                        }
                    }

                    $schedule->serviceAreaVolunteers()
                        ->wherePivot('service_area_id', $serviceArea->id)
                        ->detach();

                    if ($isPreletor && $volunteerIds === [] && $assignment['guest_name'] !== '') {
                        $schedule->update(['guest_preletor_name' => $assignment['guest_name']]);
                        $saved++;
                        continue;
                    }

                    if ($isPreletor) {
                        $schedule->update(['guest_preletor_name' => null]);
                    }

                    foreach ($volunteerIds as $volunteerId) {
                        $schedule->serviceAreaVolunteers()->attach($volunteerId, [
                            'service_area_id' => $serviceArea->id,
                            'status' => 'pendente',
                        ]);
                    }

                    $saved++;
                }
            });
        } catch (\RuntimeException $exception) {
            return $redirect('error', $exception->getMessage());
        }

        return redirect()->route('voluntarios.escalas-mensais.index', compact('month', 'year'))
            ->with('success', "Escala de {$serviceArea->name} salva com sucesso! Cultos atualizados: {$saved}.");
    }

    /**
     * Geração automática mensal por área e tipo de culto
     */
    public function generateMonthly(Request $request)
    {
        $this->authorize('create', ServiceSchedule::class);
        $validated = $request->validate([
            'service_area_id' => 'required|exists:service_areas,id',
            'culto_tipo' => 'required|in:familia,graca',
            'month' => 'required|integer|min:1|max:12',
            'year' => 'required|integer|min:2020|max:2100',
        ], [
            'service_area_id.required' => 'Selecione uma área de serviço.',
            'service_area_id.exists' => 'A área de serviço selecionada não existe.',
            'culto_tipo.required' => 'Selecione o tipo de culto.',
            'culto_tipo.in' => 'Tipo de culto inválido.',
            'month.required' => 'O mês é obrigatório.',
            'year.required' => 'O ano é obrigatório.',
        ]);

        $serviceArea = ServiceArea::findOrFail($validated['service_area_id']);
        $requiredVolunteers = $this->getRequiredVolunteersForArea($serviceArea);

        $events = $this->getCultosByType($validated['culto_tipo'], $validated['month'], $validated['year']);

        if ($events->isEmpty()) {
            return redirect()->route('voluntarios.escalas-mensais.index', [
                'month' => $validated['month'],
                'year' => $validated['year'],
            ])->with('error', 'Nenhum culto encontrado para o tipo selecionado no mês informado.');
        }

        $volunteers = Volunteer::where('status', 'ativo')
            ->whereHas('serviceAreas', function ($query) use ($serviceArea) {
                $query->where('service_areas.id', $serviceArea->id);
            })
            ->orderBy('id')
            ->get();

        if ($volunteers->count() < $requiredVolunteers) {
            return redirect()->route('voluntarios.escalas-mensais.index', [
                'month' => $validated['month'],
                'year' => $validated['year'],
            ])->with('error', "A área {$serviceArea->name} não possui voluntários ativos suficientes. Necessário: {$requiredVolunteers}.");
        }

        $createdSchedules = 0;
        $updatedSchedules = 0;
        $rotationCursor = 0;
        $previousAreaVolunteerIds = $this->getPreviousAreaVolunteerIdsForRule(
            $serviceArea->id,
            $validated['culto_tipo'],
            $events->first()?->start_date
        );

        try {
            DB::transaction(function () use (
                $validated,
                $events,
                $serviceArea,
                $volunteers,
                $requiredVolunteers,
                &$createdSchedules,
                &$updatedSchedules,
                &$rotationCursor,
                &$previousAreaVolunteerIds
            ) {
                foreach ($events as $event) {
                    $schedule = MonthlyCultoSchedule::where('event_id', $event->id)
                        ->where('month', $validated['month'])
                        ->where('year', $validated['year'])
                        ->first();

                    if (!$schedule) {
                        $schedule = MonthlyCultoSchedule::create([
                            'event_id' => $event->id,
                            'month' => $validated['month'],
                            'year' => $validated['year'],
                            'status' => 'rascunho',
                        ]);
                        $createdSchedules++;
                    } else {
                        $updatedSchedules++;
                    }

                    // Remove apenas voluntários da área selecionada para regenerar sem duplicidade
                    $schedule->serviceAreaVolunteers()
                        ->wherePivot('service_area_id', $serviceArea->id)
                        ->detach();

                    $alreadyAssignedVolunteerIds = DB::table('monthly_culto_service_areas')
                        ->where('monthly_culto_schedule_id', $schedule->id)
                        ->pluck('volunteer_id')
                        ->unique()
                        ->values()
                        ->all();

                    $availableVolunteers = $volunteers
                        ->reject(function ($volunteer) use ($alreadyAssignedVolunteerIds) {
                            return in_array($volunteer->id, $alreadyAssignedVolunteerIds);
                        })
                        ->reject(function ($volunteer) use ($previousAreaVolunteerIds) {
                            return in_array($volunteer->id, $previousAreaVolunteerIds);
                        })
                        ->values();

                    if ($availableVolunteers->count() < $requiredVolunteers) {
                        throw new \RuntimeException("Regra aplicada: no culto {$event->title} ({$event->start_date->format('d/m/Y H:i')}), não há voluntários suficientes sem repetição para a área {$serviceArea->name} (mesmo culto e cultos consecutivos).");
                    }

                    $selectedVolunteerIds = [];
                    for ($i = 0; $i < $requiredVolunteers; $i++) {
                        $selectedVolunteerIds[] = $availableVolunteers[($rotationCursor + $i) % $availableVolunteers->count()]->id;
                    }
                    $rotationCursor++;

                    foreach ($selectedVolunteerIds as $volunteerId) {
                        $schedule->serviceAreaVolunteers()->attach($volunteerId, [
                            'service_area_id' => $serviceArea->id,
                            'status' => 'pendente',
                        ]);
                    }

                    $preletorArea = $this->findPreletorArea();
                    if ($preletorArea && (int) $serviceArea->id === (int) $preletorArea->id) {
                        $schedule->update(['guest_preletor_name' => null]);
                    }

                    // Regra: não pode escalar a mesma pessoa em dois cultos seguidos
                    $previousAreaVolunteerIds = $selectedVolunteerIds;
                }
            });
        } catch (\RuntimeException $exception) {
            return redirect()->route('voluntarios.escalas-mensais.index', [
                'month' => $validated['month'],
                'year' => $validated['year'],
            ])->with('error', $exception->getMessage());
        }

        return redirect()->route('voluntarios.escalas-mensais.index', [
            'month' => $validated['month'],
            'year' => $validated['year'],
        ])->with(
            'success',
            "Escala mensal gerada para {$serviceArea->name}. Cultos processados: {$events->count()} (novos: {$createdSchedules}, atualizados: {$updatedSchedules})."
        );
    }

    private function findPreletorArea(): ?ServiceArea
    {
        return ServiceArea::where('status', 'ativo')
            ->where(function ($query) {
                $query->whereRaw('LOWER(name) LIKE ?', ['%preletor%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%pregador%']);
            })
            ->first();
    }

    private function buildManualSchedulePayload($serviceAreas, int $month, int $year): array
    {
        $volunteers = Volunteer::where('status', 'ativo')
            ->whereHas('serviceAreas', function ($query) use ($serviceAreas) {
                $query->whereIn('service_areas.id', $serviceAreas->pluck('id'));
            })
            ->with(['member', 'serviceAreas'])
            ->get();

        $members = Member::where('status', Member::STATUS_ATIVO)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($member) => [
                'id' => $member->id,
                'name' => $member->name,
            ])
            ->values()
            ->all();

        $areas = [];
        foreach ($serviceAreas as $area) {
            $quantity = $this->getRequiredVolunteersForArea($area);
                $isLimpeza = $this->isLimpezaServiceArea($area);
                $areas[(string) $area->id] = [
                    'id' => $area->id,
                    'name' => $area->name,
                    'quantity' => $quantity,
                    'help' => $this->getAreaHelpText($area),
                    'rules_text' => $this->getAreaRulesText($area),
                    'slots' => $this->getAreaSlotLabels($area),
                    'is_preletor' => $this->isPreletorServiceArea($area),
                    'is_limpeza' => $isLimpeza,
                    'uses_members' => $isLimpeza,
                    'allows_overlap' => $isLimpeza,
                    'sunday_only' => $isLimpeza,
                    'volunteers' => $isLimpeza
                        ? $members
                        : $volunteers
                            ->filter(fn ($volunteer) => $volunteer->serviceAreas->contains('id', $area->id))
                            ->sortBy(fn ($volunteer) => mb_strtolower($volunteer->member->name ?? ''))
                            ->values()
                            ->map(fn ($volunteer) => [
                                'id' => $volunteer->id,
                                'name' => $volunteer->member->name ?? 'Sem nome',
                            ])
                            ->all(),
                ];
        }

        $assignments = [];
        $guests = [];
        $occupied = [];

        $monthSchedules = MonthlyCultoSchedule::with('serviceAreaVolunteers')
            ->byMonthYear($month, $year)
            ->get();

        foreach ($monthSchedules as $schedule) {
            $eventId = (string) $schedule->event_id;
            if (filled($schedule->guest_preletor_name)) {
                $guests[$eventId] = $schedule->guest_preletor_name;
            }

            foreach ($schedule->serviceAreaVolunteers as $volunteer) {
                $areaId = (string) $volunteer->pivot->service_area_id;
                $assignedArea = $serviceAreas->firstWhere('id', (int) $areaId);
                $isLimpezaAssignment = $assignedArea && $this->isLimpezaServiceArea($assignedArea);
                $valueId = $isLimpezaAssignment ? (int) $volunteer->member_id : (int) $volunteer->id;
                $assignments[$eventId][$areaId][] = $valueId;

                if ($isLimpezaAssignment) {
                    continue;
                }

                $areaName = $assignedArea->name ?? 'outra área';
                $occupied[$eventId][(string) $volunteer->id] = [
                    'area_id' => (int) $areaId,
                    'area_name' => $areaName,
                ];
            }
        }

        return [
            'areas' => $areas,
            'assignments' => $assignments,
            'guests' => $guests,
            'occupied' => $occupied,
            'old_area_id' => old('service_area_id', session('add_schedule_area_id')),
            'old_assignments' => old('assignments', []),
            'old_guests' => old('guests', []),
        ];
    }

    private function normalizeServiceAreaName(ServiceArea $serviceArea): string
    {
        return Str::of($serviceArea->name)
            ->lower()
            ->ascii()
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->value();
    }

    private function isPreletorServiceArea(ServiceArea $serviceArea): bool
    {
        $normalized = $this->normalizeServiceAreaName($serviceArea);

        return str_contains($normalized, 'preletor') || str_contains($normalized, 'pregador');
    }

    private function isLimpezaServiceArea(ServiceArea $serviceArea): bool
    {
        $normalized = $this->normalizeServiceAreaName($serviceArea);

        return str_contains($normalized, 'limpeza')
            || str_contains($normalized, 'zeladoria')
            || str_contains($normalized, 'zelador');
    }

    private function getAreaRulesText(ServiceArea $serviceArea): string
    {
        if ($this->isLimpezaServiceArea($serviceArea)) {
            return 'Somente cultos de domingo. A limpeza acontece fora do horário do culto, então a pessoa pode já estar em outra escala do dia. Cultos sem preenchimento não são alterados.';
        }

        return 'Cultos de quarta e domingo da agenda. A mesma pessoa não pode servir em áreas diferentes no mesmo culto. Cultos sem preenchimento não são alterados.';
    }

    private function getAreaHelpText(ServiceArea $serviceArea): string
    {
        $normalized = $this->normalizeServiceAreaName($serviceArea);
        $quantity = $this->getRequiredVolunteersForArea($serviceArea);
        $people = $quantity === 1 ? '1 pessoa' : "{$quantity} pessoas";

        $texts = [
            'apoio geral' => "Serve água e apoia em outras questões que aparecer. {$people}.",
            'direcao do culto' => "Responsável por direcionar o culto. {$people}.",
            'direcao' => "Responsável por direcionar o culto. {$people}.",
            'intercessao' => "Interceder durante o culto. {$people}.",
            'portaria' => "Segurança na entrada da igreja. {$people}.",
            'preletor' => "Traz o sermão à igreja. {$people} (voluntário ou convidado).",
            'recepcao' => "Recebe as pessoas nas entradas. {$people}.",
            'sala das criancas' => "Cuida do ensino das crianças durante o culto. {$people} (professora e monitor).",
            'limpeza' => "Limpeza da igreja para os cultos de domingo, sempre fora do horário do culto. {$people}, escolhidas na lista de membros.",
            'zeladoria' => "Limpeza da igreja para os cultos de domingo, sempre fora do horário do culto. {$people}, escolhidas na lista de membros.",
        ];

        foreach ($texts as $areaKey => $text) {
            if (str_contains($normalized, $areaKey)) {
                return $text;
            }
        }

        return "Selecione os voluntários desta área. {$people}.";
    }

    private function getAreaSlotLabels(ServiceArea $serviceArea): array
    {
        $normalized = $this->normalizeServiceAreaName($serviceArea);
        $quantity = $this->getRequiredVolunteersForArea($serviceArea);

        if (str_contains($normalized, 'sala das criancas')) {
            $labels = ['Professora', 'Monitor'];
            if ($quantity <= 1) {
                return ['Professora'];
            }
            for ($index = 3; $index <= $quantity; $index++) {
                $labels[] = "Voluntário {$index}";
            }

            return $labels;
        }

        if ($this->isPreletorServiceArea($serviceArea)) {
            $labels = ['Preletor(a)'];
            for ($index = 2; $index <= $quantity; $index++) {
                $labels[] = "Preletor(a) {$index}";
            }

            return $labels;
        }

        if ($quantity === 1) {
            return ['Voluntário'];
        }

        $labels = [];
        for ($index = 1; $index <= $quantity; $index++) {
            $labels[] = "Voluntário {$index}";
        }

        return $labels;
    }

    private function volunteerIdsForLimpezaMembers(array $memberIds, ServiceArea $serviceArea): array
    {
        $volunteerIds = [];

        foreach ($memberIds as $memberId) {
            $volunteer = Volunteer::withTrashed()->where('member_id', $memberId)->first();

            if ($volunteer) {
                if ($volunteer->trashed()) {
                    $volunteer->restore();
                }

                if ($volunteer->status !== 'ativo') {
                    $volunteer->update(['status' => 'ativo']);
                }
            } else {
                $volunteer = Volunteer::create([
                    'member_id' => $memberId,
                    'experience_level' => 'novo',
                    'start_date' => now()->toDateString(),
                    'status' => 'ativo',
                ]);
            }

            $volunteer->serviceAreas()->syncWithoutDetaching([$serviceArea->id]);
            $volunteerIds[] = (int) $volunteer->id;
        }

        return $volunteerIds;
    }

    private function getCultosDoMes(int $month, int $year)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        return Event::whereBetween('start_date', [$startDate, $endDate])
            ->where(function ($query) {
                $query->whereRaw('LOWER(title) LIKE ?', ['%culto%'])
                    ->orWhereHas('category', function ($q) {
                        $q->whereRaw('LOWER(name) LIKE ?', ['%culto%']);
                    });
            })
            ->orderBy('start_date')
            ->get()
            ->filter(function ($event) {
                $dayOfWeek = optional($event->start_date)->dayOfWeek;

                return in_array($dayOfWeek, [Carbon::SUNDAY, Carbon::WEDNESDAY], true);
            })
            ->values();
    }

    private function getCultosByType(string $cultoTipo, int $month, int $year)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate = Carbon::create($year, $month, 1)->endOfMonth()->endOfDay();

        $query = Event::whereBetween('start_date', [$startDate, $endDate]);

        if ($cultoTipo === 'familia') {
            $events = $query
                ->where('title', 'like', '%fam%')
                ->orderBy('start_date')
                ->get();

            return $events->filter(function ($event) {
                return optional($event->start_date)->dayOfWeek === Carbon::SUNDAY;
            })->values();
        }

        $events = $query
            ->where(function ($q) {
                $q->where('title', 'like', '%gra%')
                    ->orWhere('title', 'like', '%Gra%');
            })
            ->orderBy('start_date')
            ->get();

        return $events->filter(function ($event) {
            return optional($event->start_date)->dayOfWeek === Carbon::WEDNESDAY;
        })->values();
    }

    private function getRequiredVolunteersForArea(ServiceArea $serviceArea): int
    {
        return max(1, (int) $serviceArea->min_quantity);
    }

    private function hasDuplicateVolunteersInPayload(array $serviceAreas): bool
    {
        $allVolunteerIds = collect($serviceAreas)
            ->flatten()
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->values();

        return $allVolunteerIds->count() !== $allVolunteerIds->unique()->count();
    }

    private function getPreviousAreaVolunteerIdsForRule(int $serviceAreaId, string $cultoTipo, $firstEventDate): array
    {
        if (!$firstEventDate) {
            return [];
        }

        $firstEventDate = $firstEventDate instanceof Carbon ? $firstEventDate : Carbon::parse($firstEventDate);

        $previousEvent = $this->buildCultoTypeQuery($cultoTipo)
            ->where('start_date', '<', $firstEventDate)
            ->orderByDesc('start_date')
            ->first();

        if (!$previousEvent) {
            return [];
        }

        $previousSchedule = MonthlyCultoSchedule::where('event_id', $previousEvent->id)->first();
        if (!$previousSchedule) {
            return [];
        }

        return DB::table('monthly_culto_service_areas')
            ->where('monthly_culto_schedule_id', $previousSchedule->id)
            ->where('service_area_id', $serviceAreaId)
            ->pluck('volunteer_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function buildCultoTypeQuery(string $cultoTipo)
    {
        $query = Event::query();

        if ($cultoTipo === 'familia') {
            return $query
                ->where('title', 'like', '%fam%')
                ->whereRaw('DAYOFWEEK(start_date) = 1');
        }

        return $query
            ->where(function ($q) {
                $q->where('title', 'like', '%gra%')
                    ->orWhere('title', 'like', '%Gra%');
            })
            ->whereRaw('DAYOFWEEK(start_date) = 4');
    }

    private function buildMonthlySchedulePdfOutput(MonthlyCultoSchedule $escala): string
    {
        $escala->loadMissing(['event', 'serviceAreaVolunteers.member']);

        $serviceAreas = ServiceArea::where('status', 'ativo')->orderBy('name')->get();

        $volunteersByArea = [];
        foreach ($serviceAreas as $area) {
            $volunteersByArea[$area->id] = $escala->getVolunteersByServiceArea($area->id);
        }

        $logoBase64 = null;
        $logoPath = null;
        $logoFileName = 'LOG SS branca.png';
        $logoPublicPath = public_path("img/img/{$logoFileName}");

        if (file_exists($logoPublicPath)) {
            $logoPath = $logoPublicPath;
            $imageData = file_get_contents($logoPublicPath);
            $logoBase64 = 'data:image/png;base64,' . base64_encode($imageData);
        }

        $churchName = 'ADELSS';

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.escalas.escala-mensal', [
            'escala' => $escala,
            'serviceAreas' => $serviceAreas,
            'volunteersByArea' => $volunteersByArea,
            'logoPath' => $logoPath,
            'logoBase64' => $logoBase64,
            'churchName' => $churchName,
            'generatedAt' => now(),
        ])->setPaper('A4', 'portrait')
            ->setOption('enable-local-file-access', true)
            ->output();
    }
}
