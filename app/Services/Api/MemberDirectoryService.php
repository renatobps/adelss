<?php

namespace App\Services\Api;

use App\Models\Department;
use App\Models\Member;
use App\Models\MemberRole;
use App\Models\Pgi;

class MemberDirectoryService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function list(array $filters, int $page = 1, int $perPage = 20): array
    {
        $perPage = in_array($perPage, [10, 20, 25, 50], true) ? $perPage : 20;

        $query = Member::query()->with(['pgi:id,name', 'role:id,name']);

        $search = trim((string) ($filters['search'] ?? ''));
        if ($search !== '') {
            $query->search($search);
        }
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (! empty($filters['pgi_id'])) {
            $query->where('pgi_id', $filters['pgi_id']);
        }
        if (! empty($filters['role_id'])) {
            $query->where('role_id', $filters['role_id']);
        }

        $pageResult = $query->orderBy('name')->paginate($perPage, ['*'], 'page', $page);

        return [
            'items' => $pageResult->getCollection()->map(fn (Member $member) => [
                'id' => $member->id,
                'name' => $member->name,
                'email' => $member->email,
                'phone' => $member->phone,
                'status' => $member->status,
                'status_label' => Member::STATUSES[$member->status] ?? $member->status,
                'photo_url' => $this->photo($member),
                'pgi_name' => $member->pgi?->name,
                'role' => $member->role?->name,
                'city' => $member->city,
            ])->values()->all(),
            'page' => $pageResult->currentPage(),
            'last_page' => $pageResult->lastPage(),
            'per_page' => $pageResult->perPage(),
            'total' => $pageResult->total(),
            'kpis' => $this->kpis(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function options(): array
    {
        $statuses = [];
        foreach (Member::STATUSES as $value => $label) {
            $statuses[] = ['value' => $value, 'label' => $label];
        }

        return [
            'statuses' => $statuses,
            'pgis' => $this->safeOptions(fn () => Pgi::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Pgi $row) => ['id' => $row->id, 'name' => $row->name])
                ->all()),
            'roles' => $this->safeOptions(fn () => MemberRole::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (MemberRole $row) => ['id' => $row->id, 'name' => $row->name])
                ->all()),
            'departments' => $this->safeOptions(fn () => Department::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (Department $row) => ['id' => $row->id, 'name' => $row->name])
                ->all()),
        ];
    }

    /**
     * @return array{ativos: int, visitantes: int, pendentes: int, total: int}
     */
    private function kpis(): array
    {
        try {
            return [
                'ativos' => Member::query()->where('status', Member::STATUS_ATIVO)->count(),
                'visitantes' => Member::query()->where('status', Member::STATUS_VISITANTE)->count(),
                'pendentes' => Member::query()->where('status', Member::STATUS_PENDENTE)->count(),
                'total' => Member::query()->count(),
            ];
        } catch (\Throwable) {
            return [
                'ativos' => 0,
                'visitantes' => 0,
                'pendentes' => 0,
                'total' => 0,
            ];
        }
    }

    /**
     * @param  callable(): array<int, array<string, mixed>>  $callback
     * @return list<array<string, mixed>>
     */
    private function safeOptions(callable $callback): array
    {
        try {
            return $callback();
        } catch (\Throwable) {
            return [];
        }
    }

    private function photo(Member $member): ?string
    {
        try {
            return $member->photo_url;
        } catch (\Throwable) {
            return null;
        }
    }
}
