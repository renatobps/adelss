<?php

namespace App\Policies\Ensino;

use App\Models\Member;
use App\Models\Turma;
use App\Models\User;
use App\Policies\Concerns\ChecksModuleResourcePermissions;

class TurmaPolicy
{
    use ChecksModuleResourcePermissions;

    protected function resourcePermissionKey(): string
    {
        return 'ensino.turmas';
    }

    public function viewAny(User $user): bool
    {
        if ($user->is_admin || $this->canViewAny($user)) {
            return true;
        }

        $member = $user->member;

        return $member instanceof Member
            && ($member->turmas()->exists() || $member->isTeacherOfAnyClass());
    }

    public function view(User $user, Turma $turma): bool
    {
        if ($user->is_admin || $this->canViewAny($user)) {
            return true;
        }

        $member = $user->member;

        return $member instanceof Member
            && ($turma->isStudent($member) || $turma->isTeacher($member));
    }

    public function create(User $user): bool
    {
        return $this->canCreate($user);
    }

    public function update(User $user, Turma $turma): bool
    {
        return $this->canUpdate($user);
    }

    public function delete(User $user, Turma $turma): bool
    {
        return $this->canDelete($user);
    }

    public function manageLesson(User $user, Turma $turma): bool
    {
        if ($user->is_admin || $this->canUpdate($user)) {
            return true;
        }

        $member = $user->member;

        return $member instanceof Member && $turma->isTeacher($member);
    }

    public function manageFile(User $user, Turma $turma): bool
    {
        return $this->manageLesson($user, $turma);
    }

    public function authorizeRouteAction(User $user, string $action): bool
    {
        if (in_array($action, ['index'], true)) {
            return $this->viewAny($user);
        }

        if ($action === 'show') {
            return true;
        }

        if (in_array($action, ['storeLesson'], true)) {
            return $this->canUpdate($user);
        }

        if (in_array($action, ['storeFile'], true)) {
            return $this->canUpdate($user);
        }

        if (in_array($action, ['create', 'store'], true)) {
            return $this->create($user);
        }

        if (in_array($action, ['edit', 'update'], true)) {
            return $this->canUpdate($user, new Turma());
        }

        if ($action === 'destroy') {
            return $this->delete($user, new Turma());
        }

        return $this->viewAny($user);
    }
}
