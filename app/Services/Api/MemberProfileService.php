<?php

namespace App\Services\Api;

use App\Models\Discipleship\DiscipleshipMember;
use App\Models\FinancialTransaction;
use App\Models\Member;
use App\Models\Turma;
use App\Services\Members\MemberUserService;

class MemberProfileService
{
    public function __construct(private MemberUserService $users) {}

    /**
     * @return array<string, mixed>
     */
    public function full(Member $member): array
    {
        try {
            $member->load(['department:id,name', 'pgi:id,name', 'role:id,name']);
        } catch (\Throwable) {
        }

        return [
            'member' => $this->memberPayload($member),
            'ensino_count' => $this->ensinoCount($member),
            'discipleship_count' => $this->discipleshipCount($member),
            'contributions' => $this->contributionSummary($member),
        ];
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function create(array $input): array
    {
        if (empty($input['status'])) {
            $input['status'] = Member::STATUS_ATIVO;
        }
        foreach (['pgi_id', 'role_id', 'department_id'] as $field) {
            if (empty($input[$field])) {
                $input[$field] = null;
            }
        }
        if (array_key_exists('email', $input)) {
            $input['email'] = $this->users->normalizeEmail($input['email']);
        }

        $departments = $input['departments'] ?? [];
        unset($input['departments']);

        $member = Member::query()->create($input);
        if (! empty($departments)) {
            try {
                $member->departments()->sync($departments);
            } catch (\Throwable) {
            }
        }
        if (! empty($member->email)) {
            $this->users->syncFromMember($member->fresh(), forceDefaultPassword: true);
        }

        return $this->full($member->fresh());
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function update(Member $member, array $input, bool $staff = false): array
    {
        $email = array_key_exists('email', $input)
            ? $this->users->normalizeEmail($input['email'])
            : $member->email;

        $addressChanged = false;
        foreach (['address', 'city', 'state', 'zip_code'] as $field) {
            if (array_key_exists($field, $input) && ($input[$field] ?? null) !== $member->{$field}) {
                $addressChanged = true;
                break;
            }
        }

        $fillable = ['name', 'phone', 'gender', 'marital_status', 'birth_date', 'marriage_date', 'cpf', 'rg', 'address', 'city', 'state', 'zip_code', 'notes'];
        if ($staff) {
            $fillable = array_merge($fillable, ['status', 'pgi_id', 'role_id', 'department_id', 'membership_date']);
        }
        $data = [];
        foreach ($fillable as $field) {
            if (array_key_exists($field, $input)) {
                $data[$field] = $input[$field];
            }
        }
        if (array_key_exists('email', $input)) {
            $data['email'] = $email;
        }
        foreach (['pgi_id', 'role_id', 'department_id'] as $field) {
            if (array_key_exists($field, $data) && empty($data[$field])) {
                $data[$field] = null;
            }
        }

        $member->fill($data);

        if ($addressChanged) {
            $member->latitude = null;
            $member->longitude = null;
            $member->geocoded_at = null;
        }

        $member->save();

        if ($staff && array_key_exists('departments', $input)) {
            try {
                $member->departments()->sync($input['departments'] ?? []);
            } catch (\Throwable) {
            }
        }

        if ($member->user) {
            $this->users->syncFromMember($member->fresh());
        } elseif ($staff && ! empty($member->fresh()->email)) {
            $this->users->syncFromMember($member->fresh(), forceDefaultPassword: true);
        }

        return $this->full($member->fresh());
    }

    /**
     * @return array<string, mixed>
     */
    public function contributions(Member $member, int $limit = 30): array
    {
        $summary = $this->contributionSummary($member);

        try {
            $items = FinancialTransaction::query()
                ->where('member_id', $member->id)
                ->with(['category:id,name', 'account:id,name'])
                ->orderByDesc('transaction_date')
                ->limit($limit)
                ->get()
                ->map(fn (FinancialTransaction $row) => [
                    'id' => $row->id,
                    'date' => $row->transaction_date?->toDateString(),
                    'description' => $row->description,
                    'amount' => round((float) $row->amount, 2),
                    'paid' => (bool) $row->is_paid,
                    'category' => $row->category?->name,
                    'account' => $row->account?->name,
                ])
                ->all();
        } catch (\Throwable) {
            $items = [];
        }

        return array_merge($summary, ['items' => $items]);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function ensino(Member $member): array
    {
        $asStudent = collect();
        try {
            $asStudent = $member->turmas()->get();
            try {
                $asStudent->load('school:id,name');
            } catch (\Throwable) {
            }
        } catch (\Throwable) {
        }

        $asTeacher = collect();
        try {
            $asTeacher = Turma::query()
                ->whereHas('disciplines', function ($discipline) use ($member) {
                    $discipline->whereHas('teachers', function ($teacher) use ($member) {
                        $teacher->where('discipline_teachers.member_id', $member->id);
                    });
                })
                ->get();
            try {
                $asTeacher->load('school:id,name');
            } catch (\Throwable) {
            }
        } catch (\Throwable) {
        }

        $studentIds = $asStudent->pluck('id');
        $teacherIds = $asTeacher->pluck('id');

        return $asStudent->merge($asTeacher)->unique('id')->map(function (Turma $turma) use ($studentIds, $teacherIds) {
            $isStudent = $studentIds->contains($turma->id);
            $isTeacher = $teacherIds->contains($turma->id);
            $role = $isStudent && $isTeacher ? 'aluno_e_professor' : ($isStudent ? 'aluno' : 'professor');

            return [
                'id' => $turma->id,
                'name' => $turma->name,
                'school' => $turma->school?->name,
                'schedule' => $turma->schedule,
                'status' => $turma->status,
                'role' => $role,
                'role_label' => match ($role) {
                    'aluno_e_professor' => 'Aluno e professor',
                    'professor' => 'Professor',
                    default => 'Aluno',
                },
            ];
        })->values()->all();
    }

    /**
     * @return array{being_discipled: list<array<string, mixed>>, discipling: list<array<string, mixed>>}
     */
    public function discipleship(Member $member): array
    {
        try {
            $beingDiscipled = $member->discipleshipMembers()
                ->with(['cycle:id,nome', 'discipulador:id,name'])
                ->orderByDesc('id')
                ->get()
                ->map(fn ($row) => [
                    'id' => $row->id,
                    'role' => 'discipulado',
                    'status' => $row->status,
                    'started_at' => $row->data_inicio?->toDateString(),
                    'ended_at' => $row->data_fim?->toDateString(),
                    'cycle' => $row->cycle?->nome,
                    'mentor' => $row->discipulador?->name,
                    'disciple' => $member->name,
                ])
                ->all();

            $discipling = [];
            if ($member->user) {
                $discipling = DiscipleshipMember::query()
                    ->where('discipulador_id', $member->user->id)
                    ->with(['cycle:id,nome', 'member:id,name'])
                    ->orderByDesc('id')
                    ->get()
                    ->map(fn ($row) => [
                        'id' => $row->id,
                        'role' => 'discipulador',
                        'status' => $row->status,
                        'started_at' => $row->data_inicio?->toDateString(),
                        'ended_at' => $row->data_fim?->toDateString(),
                        'cycle' => $row->cycle?->nome,
                        'mentor' => $member->user->name,
                        'disciple' => $row->member?->name,
                    ])
                    ->all();
            }

            return [
                'being_discipled' => $beingDiscipled,
                'discipling' => $discipling,
            ];
        } catch (\Throwable) {
            return [
                'being_discipled' => [],
                'discipling' => [],
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function memberPayload(Member $member): array
    {
        $status = (string) ($member->status ?? '');

        try {
            $photoUrl = $member->photo_url;
        } catch (\Throwable) {
            $photoUrl = null;
        }

        return [
            'id' => $member->id,
            'name' => $member->name,
            'email' => $member->email,
            'phone' => $member->phone,
            'gender' => $member->gender,
            'gender_label' => $member->gender === 'F' ? 'Feminino' : ($member->gender === 'M' ? 'Masculino' : null),
            'marital_status' => $member->marital_status,
            'marital_status_label' => $this->maritalLabel($member->marital_status),
            'birth_date' => $member->birth_date?->toDateString(),
            'marriage_date' => $member->marriage_date?->toDateString(),
            'membership_date' => $member->membership_date?->toDateString(),
            'photo_url' => $photoUrl,
            'status' => $status,
            'status_label' => Member::STATUSES[$status] ?? $status,
            'cpf' => $member->cpf,
            'rg' => $member->rg,
            'address' => $member->address,
            'city' => $member->city,
            'state' => $member->state,
            'zip_code' => $member->zip_code,
            'full_address' => $member->fullAddress(),
            'notes' => $member->notes,
            'pgi_id' => $member->pgi_id,
            'pgi_name' => $member->pgi?->name,
            'department_id' => $member->department_id,
            'department' => $member->department?->name,
            'role_id' => $member->role_id,
            'role' => $member->role?->name,
        ];
    }

    /**
     * @return array{dizimo_total: float, oferta_total: float, total: float}
     */
    private function contributionSummary(Member $member): array
    {
        try {
            $dizimo = (float) FinancialTransaction::query()
                ->where('member_id', $member->id)
                ->whereHas('category', function ($query) {
                    $query->where('name', 'like', '%Dizimo%')
                        ->orWhere('name', 'like', '%Dízimo%');
                })
                ->sum('amount');

            $oferta = (float) FinancialTransaction::query()
                ->where('member_id', $member->id)
                ->whereHas('category', function ($query) {
                    $query->where('name', 'like', '%Oferta%');
                })
                ->sum('amount');
        } catch (\Throwable) {
            $dizimo = 0;
            $oferta = 0;
        }

        return [
            'dizimo_total' => round($dizimo, 2),
            'oferta_total' => round($oferta, 2),
            'total' => round($dizimo + $oferta, 2),
        ];
    }

    private function ensinoCount(Member $member): int
    {
        return count($this->ensino($member));
    }

    private function discipleshipCount(Member $member): int
    {
        $data = $this->discipleship($member);

        return count($data['being_discipled'] ?? []) + count($data['discipling'] ?? []);
    }

    private function maritalLabel(?string $value): ?string
    {
        return match ($value) {
            'solteiro' => 'Solteiro(a)',
            'casado' => 'Casado(a)',
            'divorciado' => 'Divorciado(a)',
            'viuvo' => 'Viúvo(a)',
            'uniao_estavel' => 'União estável',
            default => $value,
        };
    }
}
