<?php

namespace App\Services\Api;

use App\Models\User;
use App\Support\ModuleMenu;

class UserApiProfile
{
    /**
     * @return array{
     *     user: array<string, mixed>,
     *     permissions: list<string>,
     *     member: array<string, mixed>|null,
     *     modules: list<array{key: string, label: string}>
     * }
     */
    public function for(User $user): array
    {
        if (! $user->is_admin) {
            $user->loadMissing('permissions');
        }
        if ($user->member_id) {
            $user->loadMissing(['member.role.permissions']);
        }

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'is_admin' => (bool) $user->is_admin,
                'must_change_password' => (bool) $user->must_change_password,
            ],
            'permissions' => $user->permissionKeys(),
            'member' => $this->memberPayload($user),
            'modules' => $this->modulesFor($user),
        ];
    }

    /**
     * @return list<array{key: string, label: string}>
     */
    private function modulesFor(User $user): array
    {
        try {
            return collect(ModuleMenu::itemsFor($user))
                ->map(fn (array $item) => [
                    'key' => (string) ($item['key'] ?? ''),
                    'label' => (string) ($item['label'] ?? ''),
                ])
                ->filter(fn (array $item) => $item['key'] !== '')
                ->values()
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<string, mixed>|null
     */
    private function memberPayload(User $user): ?array
    {
        $member = $user->member;
        if (! $member) {
            return null;
        }

        return [
            'id' => $member->id,
            'name' => $member->name,
            'phone' => $member->phone,
            'status' => $member->status,
            'pgi_id' => $member->pgi_id,
            'photo_url' => $member->photo_url,
        ];
    }
}
