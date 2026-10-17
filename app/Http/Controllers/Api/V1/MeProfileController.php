<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Api\MemberProfileService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MeProfileController extends Controller
{
    use ResolvesApiMember;

    public function __construct(private MemberProfileService $profiles) {}

    public function show(Request $request): JsonResponse
    {
        $member = $this->requireMember($request);
        if ($member instanceof JsonResponse) {
            return $member;
        }

        return ApiResponse::success($this->profiles->full($member));
    }

    public function update(Request $request): JsonResponse
    {
        $member = $this->requireMember($request);
        if ($member instanceof JsonResponse) {
            return $member;
        }

        $nullable = ['email', 'phone', 'cpf', 'rg', 'address', 'city', 'state', 'zip_code', 'notes'];
        $merge = [];
        foreach ($nullable as $field) {
            if ($request->exists($field)) {
                $merge[$field] = $this->blankToNull($request->input($field));
            }
        }
        if ($merge !== []) {
            $request->merge($merge);
        }

        $emailRules = [
            'nullable',
            'email',
            Rule::unique('members', 'email')->ignore($member->id),
        ];
        if ($member->user) {
            $emailRules[] = Rule::unique('users', 'email')->ignore($member->user->id);
        } else {
            $emailRules[] = Rule::unique('users', 'email');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => $emailRules,
            'phone' => ['nullable', 'string', 'max:20'],
            'gender' => ['nullable', 'in:M,F'],
            'marital_status' => ['nullable', 'in:solteiro,casado,divorciado,viuvo,uniao_estavel'],
            'birth_date' => ['nullable', 'date'],
            'marriage_date' => ['nullable', 'date'],
            'cpf' => ['nullable', 'string', 'max:20', Rule::unique('members', 'cpf')->ignore($member->id)],
            'rg' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:2'],
            'zip_code' => ['nullable', 'string', 'max:10'],
            'notes' => ['nullable', 'string'],
        ], [
            'name.required' => 'Informe o nome.',
            'email.email' => 'Informe um e-mail válido.',
            'email.unique' => 'Este e-mail já está em uso.',
            'cpf.unique' => 'Este CPF já está em uso.',
        ]);

        return ApiResponse::success($this->profiles->update($member, $validated));
    }

    public function contributions(Request $request): JsonResponse
    {
        $member = $this->requireMember($request);
        if ($member instanceof JsonResponse) {
            return $member;
        }

        $limit = min(50, max(1, (int) $request->query('limit', 30)));

        return ApiResponse::success($this->profiles->contributions($member, $limit));
    }

    public function ensino(Request $request): JsonResponse
    {
        $member = $this->requireMember($request);
        if ($member instanceof JsonResponse) {
            return $member;
        }

        return ApiResponse::success($this->profiles->ensino($member));
    }

    public function discipleship(Request $request): JsonResponse
    {
        $member = $this->requireMember($request);
        if ($member instanceof JsonResponse) {
            return $member;
        }

        return ApiResponse::success($this->profiles->discipleship($member));
    }

    private function blankToNull(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);

        return $trimmed === '' ? null : $trimmed;
    }
}
