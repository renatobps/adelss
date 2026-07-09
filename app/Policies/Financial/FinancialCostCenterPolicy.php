<?php

namespace App\Policies\Financial;

use App\Models\FinancialCostCenter;
use App\Models\User;
use App\Policies\Concerns\ChecksFinancialResourcePermissions;

class FinancialCostCenterPolicy
{
    use ChecksFinancialResourcePermissions;

    protected function resourcePermissionKey(): string
    {
        return 'financial.cost-centers';
    }

    public function viewAny(User $user): bool
    {
        return $this->canViewAny($user);
    }

    public function view(User $user, FinancialCostCenter $costCenter): bool
    {
        return $this->canViewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->canCreate($user);
    }

    public function update(User $user, FinancialCostCenter $costCenter): bool
    {
        return $this->canUpdate($user);
    }

    public function delete(User $user, FinancialCostCenter $costCenter): bool
    {
        return $this->canDelete($user);
    }
}
