<?php

namespace App\Policies\Agenda;

use App\Models\EventCategory;
use App\Models\User;
use App\Policies\Concerns\ChecksModuleResourcePermissions;

class EventCategoryPolicy
{
    use ChecksModuleResourcePermissions;

    protected function resourcePermissionKey(): string
    {
        return 'agenda.categories';
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, EventCategory $category): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $this->canCreate($user);
    }

    public function update(User $user, EventCategory $category): bool
    {
        return $this->canUpdate($user);
    }

    public function delete(User $user, EventCategory $category): bool
    {
        return $this->canDelete($user);
    }
}
