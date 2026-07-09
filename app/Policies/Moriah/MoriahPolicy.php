<?php

namespace App\Policies\Moriah;

use App\Models\User;

class MoriahPolicy
{
    private const VIEW_ONLY_ROUTES = [
        'moriah.ministerio',
        'moriah.members.functions.get',
        'moriah.schedules.index',
        'moriah.schedules.show',
        'moriah.repertorio.index',
        'moriah.repertorio.songs.show',
        'moriah.funcoes.index',
        'moriah.unavailabilities.index',
    ];

    public function viewAny(User $user): bool
    {
        return $user->is_admin || $user->hasPermission('moriah.view') || $user->hasPermission('moriah.manage');
    }

    public function manage(User $user): bool
    {
        return $user->is_admin || $user->hasPermission('moriah.manage');
    }

    public function isViewOnlyRoute(?string $routeName): bool
    {
        return in_array($routeName, self::VIEW_ONLY_ROUTES, true);
    }
}
