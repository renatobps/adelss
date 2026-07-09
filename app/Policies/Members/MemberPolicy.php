<?php

namespace App\Policies\Members;

use App\Models\Member;
use App\Models\User;

class MemberPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin || $this->canViewMembers($user);
    }

    public function view(User $user, Member $member): bool
    {
        return $user->is_admin
            || $this->canViewMembers($user)
            || $this->isOwnProfile($user, $member);
    }

    public function create(User $user): bool
    {
        return $user->is_admin || $this->canCreateMembers($user);
    }

    public function update(User $user, Member $member): bool
    {
        return $user->is_admin
            || $this->canEditMembers($user)
            || $this->isOwnProfile($user, $member);
    }

    public function delete(User $user, Member $member): bool
    {
        return $user->is_admin || $this->canDeleteMembers($user);
    }

    public function import(User $user): bool
    {
        return $this->create($user);
    }

    public function downloadTemplate(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function importTutorial(User $user): bool
    {
        return $this->viewAny($user);
    }

    private function canViewMembers(User $user): bool
    {
        return $user->hasPermission('members.index.view')
            || $user->hasPermission('members.view')
            || $user->hasPermission('members.index.manage');
    }

    private function canCreateMembers(User $user): bool
    {
        return $user->hasPermission('members.index.create')
            || $user->hasPermission('members.create')
            || $user->hasPermission('members.index.manage');
    }

    private function canEditMembers(User $user): bool
    {
        return $user->hasPermission('members.index.edit')
            || $user->hasPermission('members.edit')
            || $user->hasPermission('members.index.manage');
    }

    private function canDeleteMembers(User $user): bool
    {
        return $user->hasPermission('members.index.delete')
            || $user->hasPermission('members.delete')
            || $user->hasPermission('members.index.manage');
    }

    private function isOwnProfile(User $user, Member $member): bool
    {
        $loggedMember = $user->member;

        return $loggedMember !== null && (int) $loggedMember->id === (int) $member->id;
    }
}
