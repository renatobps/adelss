<?php

namespace App\Policies\Members;

use App\Models\MemberRole;
use App\Models\User;

class MemberRolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin
            || $user->hasPermission('members.roles.view')
            || $user->hasPermission('members.roles.manage');
    }

    public function view(User $user, MemberRole $memberRole): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->is_admin
            || $user->hasPermission('members.roles.create')
            || $user->hasPermission('members.roles.manage');
    }

    public function update(User $user, MemberRole $memberRole): bool
    {
        return $user->is_admin
            || $user->hasPermission('members.roles.edit')
            || $user->hasPermission('members.roles.manage');
    }

    public function delete(User $user, MemberRole $memberRole): bool
    {
        return $user->is_admin
            || $user->hasPermission('members.roles.delete')
            || $user->hasPermission('members.roles.manage');
    }

    public function import(User $user): bool
    {
        return $this->create($user);
    }

    public function downloadTemplate(User $user): bool
    {
        return $this->viewAny($user);
    }
}
