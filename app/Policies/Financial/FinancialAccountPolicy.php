<?php

namespace App\Policies\Financial;

use App\Models\FinancialAccount;
use App\Models\User;
use App\Policies\Concerns\ChecksFinancialResourcePermissions;

class FinancialAccountPolicy
{
    use ChecksFinancialResourcePermissions;

    protected function resourcePermissionKey(): string
    {
        return 'financial.accounts';
    }

    public function viewAny(User $user): bool
    {
        return $this->canViewAny($user);
    }

    public function view(User $user, FinancialAccount $account): bool
    {
        return $this->canViewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->canCreate($user);
    }

    public function update(User $user, FinancialAccount $account): bool
    {
        return $this->canUpdate($user);
    }

    public function delete(User $user, FinancialAccount $account): bool
    {
        return $this->canDelete($user);
    }
}
