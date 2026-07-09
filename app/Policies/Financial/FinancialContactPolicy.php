<?php

namespace App\Policies\Financial;

use App\Models\FinancialContact;
use App\Models\User;
use App\Policies\Concerns\ChecksFinancialResourcePermissions;

class FinancialContactPolicy
{
    use ChecksFinancialResourcePermissions;

    protected function resourcePermissionKey(): string
    {
        return 'financial.contacts';
    }

    public function viewAny(User $user): bool
    {
        return $this->canViewAny($user);
    }

    public function view(User $user, FinancialContact $contact): bool
    {
        return $this->canViewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->canCreate($user);
    }

    public function update(User $user, FinancialContact $contact): bool
    {
        return $this->canUpdate($user);
    }

    public function delete(User $user, FinancialContact $contact): bool
    {
        return $this->canDelete($user);
    }
}
