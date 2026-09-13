<?php

namespace App\Http\Controllers;

use App\Models\ServiceArea;
use App\Models\Department;
use App\Models\Member;
use App\Models\Volunteer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceAreaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', ServiceArea::class);
        $query = ServiceArea::with(['leader', 'parent']);

        // Busca
        if ($request->has('search') && $request->search) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        // Filtro por status
        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        // Filtro por público permitido
        if ($request->has('allowed_audience') && $request->allowed_audience) {
            $query->where('allowed_audience', $request->allowed_audience);
        }

        $serviceAreas = $query->orderBy('name')->paginate(15);

        return view('service-areas.index', compact('serviceAreas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $this->authorize('create', ServiceArea::class);
        $members = Member::orderBy('name')->get();
        $departments = Department::active()
            ->with(['members' => function ($query) {
                $query->orderBy('name');
            }])
            ->orderBy('name')
            ->get();
        
        return view('service-areas.create', compact('members', 'departments'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->authorize('create', ServiceArea::class);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:ativo,inativo',
            'leader_id' => 'nullable|exists:members,id',
            'min_quantity' => 'required|integer|min:1',
            'allowed_audience' => 'required|in:adulto,jovem,ambos',
            'whatsapp_group_jid' => 'nullable|string|max:80',
            'whatsapp_group_name' => 'nullable|string|max:150',
            'department_ids' => 'nullable|array',
            'department_ids.*' => 'exists:departments,id',
            'participant_member_ids' => 'nullable|array',
            'participant_member_ids.*' => 'exists:members,id',
        ], [
            'name.required' => 'O campo nome da área é obrigatório.',
            'name.string' => 'O nome da área deve ser um texto.',
            'name.max' => 'O nome da área não pode ter mais de 255 caracteres.',
            'status.required' => 'O campo status é obrigatório.',
            'status.in' => 'O status deve ser: ativo ou inativo.',
            'leader_id.exists' => 'O responsável selecionado não existe.',
            'min_quantity.required' => 'A quantidade mínima é obrigatória.',
            'min_quantity.integer' => 'A quantidade mínima deve ser um número inteiro.',
            'min_quantity.min' => 'A quantidade mínima deve ser pelo menos 1.',
            'allowed_audience.required' => 'O campo público permitido é obrigatório.',
            'allowed_audience.in' => 'O público permitido deve ser: adulto, jovem ou ambos.',
            'department_ids.array' => 'A lista de departamentos é inválida.',
            'department_ids.*.exists' => 'Um dos departamentos selecionados não existe.',
            'participant_member_ids.array' => 'A lista de voluntários participantes é inválida.',
            'participant_member_ids.*.exists' => 'Um dos membros selecionados não existe.',
        ]);

        $areaData = $this->normalizeWhatsAppGroup(
            collect($validated)->only([
                'name',
                'description',
                'status',
                'leader_id',
                'min_quantity',
                'allowed_audience',
                'whatsapp_group_jid',
                'whatsapp_group_name',
            ])->all()
        );

        DB::transaction(function () use ($areaData, $validated) {
            $area = ServiceArea::create($areaData);
            $this->syncParticipantVolunteers(
                $area,
                $validated['department_ids'] ?? [],
                $validated['participant_member_ids'] ?? []
            );
        });

        return redirect()->route('voluntarios.areas.index')
            ->with('success', 'Área de serviço cadastrada com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(ServiceArea $area)
    {
        $this->authorize('view', $area);
        $area->load('leader', 'volunteers.member', 'parent');
        
        return view('service-areas.show', compact('area'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(ServiceArea $area)
    {
        $this->authorize('update', $area);
        $members = Member::orderBy('name')->get();
        $departments = Department::active()
            ->with(['members' => function ($query) {
                $query->orderBy('name');
            }])
            ->orderBy('name')
            ->get();
        $area->load('volunteers.member', 'parent');
        
        return view('service-areas.edit', compact('area', 'members', 'departments'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ServiceArea $area)
    {
        $this->authorize('update', $area);
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:ativo,inativo',
            'leader_id' => 'nullable|exists:members,id',
            'min_quantity' => 'required|integer|min:1',
            'allowed_audience' => 'required|in:adulto,jovem,ambos',
            'whatsapp_group_jid' => 'nullable|string|max:80',
            'whatsapp_group_name' => 'nullable|string|max:150',
            'department_ids' => 'nullable|array',
            'department_ids.*' => 'exists:departments,id',
            'participant_member_ids' => 'nullable|array',
            'participant_member_ids.*' => 'exists:members,id',
        ], [
            'name.required' => 'O campo nome da área é obrigatório.',
            'name.string' => 'O nome da área deve ser um texto.',
            'name.max' => 'O nome da área não pode ter mais de 255 caracteres.',
            'status.required' => 'O campo status é obrigatório.',
            'status.in' => 'O status deve ser: ativo ou inativo.',
            'leader_id.exists' => 'O responsável selecionado não existe.',
            'min_quantity.required' => 'A quantidade mínima é obrigatória.',
            'min_quantity.integer' => 'A quantidade mínima deve ser um número inteiro.',
            'min_quantity.min' => 'A quantidade mínima deve ser pelo menos 1.',
            'allowed_audience.required' => 'O campo público permitido é obrigatório.',
            'allowed_audience.in' => 'O público permitido deve ser: adulto, jovem ou ambos.',
            'department_ids.array' => 'A lista de departamentos é inválida.',
            'department_ids.*.exists' => 'Um dos departamentos selecionados não existe.',
            'participant_member_ids.array' => 'A lista de voluntários participantes é inválida.',
            'participant_member_ids.*.exists' => 'Um dos membros selecionados não existe.',
        ]);

        $areaData = $this->normalizeWhatsAppGroup(
            collect($validated)->only([
                'name',
                'description',
                'status',
                'leader_id',
                'min_quantity',
                'allowed_audience',
                'whatsapp_group_jid',
                'whatsapp_group_name',
            ])->all()
        );

        DB::transaction(function () use ($area, $areaData, $validated) {
            $area->update($areaData);
            $this->syncParticipantVolunteers(
                $area,
                $validated['department_ids'] ?? [],
                $validated['participant_member_ids'] ?? []
            );
        });

        return redirect()->route('voluntarios.areas.index')
            ->with('success', 'Área de serviço atualizada com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ServiceArea $area)
    {
        $this->authorize('delete', $area);
        $area->delete();

        return redirect()->route('voluntarios.areas.index')
            ->with('success', 'Área de serviço removida com sucesso!');
    }

    private function syncParticipantVolunteers(ServiceArea $area, array $departmentIds = [], array $memberIds = []): void
    {
        $departmentMemberIds = Department::whereIn('id', $departmentIds)
            ->with('members:id')
            ->get()
            ->flatMap(function ($department) {
                return $department->members->pluck('id');
            });

        $allMemberIds = collect($memberIds)
            ->merge($departmentMemberIds)
            ->filter()
            ->unique()
            ->values();

        if ($allMemberIds->isEmpty()) {
            $area->volunteers()->sync([]);
            return;
        }

        $existingVolunteers = Volunteer::withTrashed()
            ->whereIn('member_id', $allMemberIds)
            ->get()
            ->keyBy('member_id');

        $volunteerIds = $allMemberIds->map(function ($memberId) use ($existingVolunteers) {
            $volunteer = $existingVolunteers->get($memberId);

            if ($volunteer) {
                if ($volunteer->trashed()) {
                    $volunteer->restore();
                }

                return $volunteer->id;
            }

            return Volunteer::create([
                'member_id' => $memberId,
                'experience_level' => 'novo',
                'start_date' => now()->toDateString(),
                'status' => 'ativo',
            ])->id;
        })->filter()->values()->all();

        $area->volunteers()->sync($volunteerIds);
    }

    private function normalizeWhatsAppGroup(array $areaData): array
    {
        $jid = trim((string) ($areaData['whatsapp_group_jid'] ?? ''));
        $areaData['whatsapp_group_jid'] = $jid !== '' ? $jid : null;
        $areaData['whatsapp_group_name'] = $jid !== ''
            ? (trim((string) ($areaData['whatsapp_group_name'] ?? '')) ?: null)
            : null;

        return $areaData;
    }

    public function listWhatsAppGroups(\App\Services\WhatsAppService $whatsapp)
    {
        $this->authorize('viewAny', ServiceArea::class);

        $result = $whatsapp->listGroups();
        if (! ($result['success'] ?? false)) {
            return response()->json([
                'success' => false,
                'message' => $result['error'] ?? 'Falha ao listar grupos.',
                'groups' => [],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'groups' => $result['groups'] ?? [],
        ]);
    }
}
