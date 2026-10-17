<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\User;
use App\Services\Api\MemberDirectoryService;
use App\Services\Api\MemberProfileService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MembersController extends Controller
{
    public function __construct(
        private MemberDirectoryService $directory,
        private MemberProfileService $profiles,
    ) {}

    public function index(Request $request): JsonResponse
    {
        if ($request->user()->cannot('viewAny', Member::class)) {
            return ApiResponse::error('Você não tem acesso ao módulo de membros.', 403, [
                'code' => 'members_forbidden',
            ]);
        }

        $perPage = (int) $request->query('per_page', 20);
        $page = max(1, (int) $request->query('page', 1));
        $payload = $this->directory->list($request->query(), $page, $perPage);
        $payload['can_create'] = $request->user()->can('create', Member::class);
        $payload['can_edit'] = $this->canEditChurchFields($request->user());

        return ApiResponse::success($payload);
    }

    public function options(Request $request): JsonResponse
    {
        if ($request->user()->cannot('viewAny', Member::class) && $request->user()->cannot('create', Member::class)) {
            return ApiResponse::error('Você não tem acesso ao módulo de membros.', 403, [
                'code' => 'members_forbidden',
            ]);
        }

        return ApiResponse::success($this->directory->options());
    }

    public function show(Request $request, Member $member): JsonResponse
    {
        if ($request->user()->cannot('view', $member)) {
            return ApiResponse::error('Você não pode ver este membro.', 403, [
                'code' => 'members_forbidden',
            ]);
        }

        $payload = $this->profiles->full($member);
        $payload['can_edit'] = $request->user()->can('update', $member);
        $payload['can_edit_church_fields'] = $this->canEditChurchFields($request->user());

        return ApiResponse::success($payload);
    }

    public function store(Request $request): JsonResponse
    {
        if ($request->user()->cannot('create', Member::class)) {
            return ApiResponse::error('Você não pode cadastrar membros.', 403, [
                'code' => 'members_forbidden',
            ]);
        }

        $validated = $this->validated($request);

        return ApiResponse::success($this->profiles->create($validated), [], 201);
    }

    public function update(Request $request, Member $member): JsonResponse
    {
        if ($request->user()->cannot('update', $member)) {
            return ApiResponse::error('Você não pode editar este membro.', 403, [
                'code' => 'members_forbidden',
            ]);
        }

        $staff = $this->canEditChurchFields($request->user());
        $validated = $this->validated($request, $member, $staff);

        return ApiResponse::success($this->profiles->update($member, $validated, $staff));
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Member $member = null, bool $staff = true): array
    {
        $nullable = ['email', 'phone', 'cpf', 'rg', 'address', 'city', 'state', 'zip_code', 'notes'];
        $merge = [];
        foreach ($nullable as $field) {
            if ($request->exists($field)) {
                $value = $request->input($field);
                $merge[$field] = is_string($value) && trim($value) === '' ? null : $value;
            }
        }
        if ($merge !== []) {
            $request->merge($merge);
        }

        $emailRules = ['nullable', 'email', Rule::unique('members', 'email')->ignore($member?->id)];
        if ($member?->user) {
            $emailRules[] = Rule::unique('users', 'email')->ignore($member->user->id);
        } else {
            $emailRules[] = Rule::unique('users', 'email');
        }

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => $emailRules,
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'in:M,F'],
            'marital_status' => ['nullable', 'in:solteiro,casado,divorciado,viuvo,uniao_estavel'],
            'birth_date' => ['nullable', 'date'],
            'marriage_date' => ['nullable', 'date'],
            'cpf' => ['nullable', 'string', 'max:20', Rule::unique('members', 'cpf')->ignore($member?->id)],
            'rg' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:2'],
            'zip_code' => ['nullable', 'string', 'max:10'],
            'notes' => ['nullable', 'string'],
        ];

        if ($staff) {
            $rules['status'] = [$member ? 'nullable' : 'required', Rule::in(array_keys(Member::STATUSES))];
            $rules['membership_date'] = ['nullable', 'date'];
            $rules['pgi_id'] = ['nullable', 'integer'];
            $rules['role_id'] = ['nullable', 'integer'];
            $rules['department_id'] = ['nullable', 'integer'];
            $rules['departments'] = ['nullable', 'array'];
            $rules['departments.*'] = ['integer'];
        }

        return $request->validate($rules, [
            'name.required' => 'Informe o nome.',
            'email.unique' => 'Este e-mail já está em uso.',
            'cpf.unique' => 'Este CPF já está em uso.',
        ]);
    }

    private function canEditChurchFields(User $user): bool
    {
        return $user->can('create', Member::class)
            || $user->hasPermission('members.index.edit')
            || $user->hasPermission('members.edit')
            || $user->hasPermission('members.index.manage');
    }
}
