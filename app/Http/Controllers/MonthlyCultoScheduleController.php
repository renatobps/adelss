<?php

namespace App\Http\Controllers;

use App\Models\MonthlyCultoSchedule;
use App\Models\Event;
use App\Models\Member;
use App\Models\Volunteer;
use App\Models\ServiceArea;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class MonthlyCultoScheduleController extends Controller
{
    /**
     * Lista mensal de escalas de cultos
     */
    public function index(Request $request)
    {
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

        // Buscar todas as áreas de serviço para exibição
        $serviceAreas = ServiceArea::where('status', 'ativo')->orderBy('name')->get();

        return view('monthly-culto-schedules.index', compact('schedules', 'month', 'year', 'serviceAreas'));
    }

    /**
     * Mostra formulário para selecionar mês e listar cultos
     */
    public function create(Request $request)
    {
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
        $validated = $request->validate([
            'service_areas' => 'nullable|array',
            'service_areas.*' => 'nullable|array',
            'service_areas.*.*' => 'exists:volunteers,id',
        ], [
            'service_areas.array' => 'As áreas de serviço devem ser uma lista válida.',
        ]);

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

        return redirect()->route('voluntarios.escalas-mensais.index', ['month' => $escala->month, 'year' => $escala->year])
            ->with('success', 'Escala mensal atualizada com sucesso!');
    }

    /**
     * Remove escala mensal
     */
    public function destroy(MonthlyCultoSchedule $escala)
    {
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

        return view('monthly-culto-schedules.show', compact('escala', 'serviceAreas', 'volunteersByArea'));
    }

    /**
     * Atualizar status da escala
     */
    public function updateStatus(Request $request, MonthlyCultoSchedule $escala)
    {
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
        $escala->update(['status' => 'cancelada']);

        return redirect()->route('voluntarios.escalas-mensais.show', $escala)
            ->with('success', 'Escala cancelada com sucesso!');
    }

    /**
     * Gerar PDF da escala usando DOMPDF
     */
    public function generatePdf(MonthlyCultoSchedule $escala)
    {
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

        // Verificar se o novo voluntário já está na mesma área
        $existing = \DB::table('monthly_culto_service_areas')
            ->where('monthly_culto_schedule_id', $pivot->monthly_culto_schedule_id)
            ->where('service_area_id', $pivot->service_area_id)
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
        $serviceAreaId = $request->get('service_area_id');
        
        if (!$serviceAreaId) {
            return response()->json([
                'success' => false,
                'message' => 'Área de serviço não informada'
            ], 400);
        }

        $volunteers = Volunteer::where('status', 'ativo')
            ->whereHas('serviceAreas', function($query) use ($serviceAreaId) {
                $query->where('service_areas.id', $serviceAreaId);
            })
            ->with('member')
            ->orderBy('id')
            ->get()
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
}
