<?php

namespace App\Policies\Financial;

use App\Models\FinancialCategory;
use App\Models\User;
use App\Policies\Concerns\ChecksFinancialResourcePermissions;

class FinancialCategoryPolicy
{
    use ChecksFinancialResourcePermissions;

    protected function resourcePermissionKey(): string
    {
        return 'financial.categories';
    }

    public function viewAny(User $user): bool
    {
        return $this->canViewAny($user);
    }

    public function view(User $user, FinancialCategory $category): bool
    {
        return $this->canViewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->canCreate($user);
    }

    public function update(User $user, FinancialCategory $category): bool
    {
        return $this->canUpdate($user);
    }

    public function delete(User $user, FinancialCategory $category): bool
    {
        return $this->canDelete($user);
    }
}
