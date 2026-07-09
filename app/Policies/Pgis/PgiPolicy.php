<?php

namespace App\Policies\Pgis;

use App\Models\Member;
use App\Models\Pgi;
use App\Models\User;

class PgiPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin
            || $this->hasViewPermission($user)
            || $this->belongsToAnyPgi($user)
            || $this->isLeaderOfAnyPgi($user);
    }

    public function view(User $user, Pgi $pgi): bool
    {
        return $user->is_admin
            || $this->hasViewPermission($user)
            || $this->isMemberOfPgi($user, $pgi)
            || $this->isLeaderOfPgi($user, $pgi);
    }

    public function create(User $user): bool
    {
        return $user->is_admin
            || $user->hasPermission('pgis.index.create')
            || $user->hasPermission('pgis.index.manage');
    }

    public function update(User $user, Pgi $pgi): bool
    {
        return $user->is_admin
            || $user->hasPermission('pgis.index.edit')
            || $user->hasPermission('pgis.index.manage');
    }

    public function delete(User $user, Pgi $pgi): bool
    {
        return $user->is_admin
            || $user->hasPermission('pgis.index.delete')
            || $user->hasPermission('pgis.index.manage');
    }

    public function manageMeetings(User $user, Pgi $pgi): bool
    {
        return $user->is_admin || $this->isLeaderOfPgi($user, $pgi);
    }

    public function sendNotification(User $user, Pgi $pgi): bool
    {
        return $this->manageMeetings($user, $pgi);
    }

    public function attachMembers(User $user, Pgi $pgi): bool
    {
        return $this->viewAny($user);
    }

    public function detachMember(User $user, Pgi $pgi): bool
    {
        return $this->viewAny($user);
    }

    public function updateLogo(User $user, Pgi $pgi): bool
    {
        return $this->viewAny($user);
    }

    public function updateBanner(User $user, Pgi $pgi): bool
    {
        return $this->viewAny($user);
    }

    private function hasViewPermission(User $user): bool
    {
        return $user->hasPermission('pgis.index.view')
            || $user->hasPermission('pgis.index.manage');
    }

    private function belongsToAnyPgi(User $user): bool
    {
        $member = $user->member;

        return $member !== null && (bool) $member->pgi_id;
    }

    private function isLeaderOfAnyPgi(User $user): bool
    {
        $member = $user->member;

        if ($member === null) {
            return false;
        }

        return Pgi::where(function ($query) use ($member) {
            $query->where('leader_1_id', $member->id)
                ->orWhere('leader_2_id', $member->id)
                ->orWhere('leader_training_1_id', $member->id)
                ->orWhere('leader_training_2_id', $member->id);
        })->exists();
    }

    private function isMemberOfPgi(User $user, Pgi $pgi): bool
    {
        $member = $user->member;

        return $member !== null
            && $member->pgi_id
            && (int) $member->pgi_id === (int) $pgi->id;
    }

    private function isLeaderOfPgi(User $user, Pgi $pgi): bool
    {
        $member = $user->member;

        return $member instanceof Member && $pgi->isLeader($member);
    }
}
