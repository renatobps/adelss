<?php

namespace App\Policies\Financial;

use App\Models\User;

class FinancialModulePolicy
{
    public function viewSummary(User $user): bool
    {
        return $user->is_admin
            || $user->hasPermission('financial.receitas.view')
            || $user->hasPermission('financial.despesas.view')
            || $user->hasPermission('financial.receitas.manage')
            || $user->hasPermission('financial.despesas.manage');
    }

    public function viewReports(User $user): bool
    {
        return $user->is_admin
            || $user->hasPermission('financial.reports.view')
            || $user->hasPermission('financial.reports.manage');
    }

    public function viewFechamento(User $user): bool
    {
        return $user->is_admin
            || $user->hasPermission('financial.fechamento.view')
            || $user->hasPermission('financial.fechamento.manage')
            || $user->hasPermission('financial.fechamento.generate');
    }

    public function generateFechamento(User $user): bool
    {
        return $user->is_admin
            || $user->hasPermission('financial.fechamento.generate')
            || $user->hasPermission('financial.fechamento.manage');
    }

    public function viewAutomations(User $user): bool
    {
        return $this->viewSummary($user) || $this->manageAutomations($user);
    }

    public function manageAutomations(User $user): bool
    {
        return $user->is_admin
            || $user->hasPermission('financial.receitas.manage')
            || $user->hasPermission('financial.despesas.manage');
    }

    public function viewFixedExpenses(User $user): bool
    {
        return $user->is_admin
            || $user->hasPermission('financial.despesas.view')
            || $user->hasPermission('financial.despesas.manage')
            || $user->hasPermission('financial.despesas.create');
    }

    public function manageFixedExpenses(User $user): bool
    {
        return $user->is_admin
            || $user->hasPermission('financial.despesas.manage')
            || $user->hasPermission('financial.despesas.create')
            || $user->hasPermission('financial.despesas.edit');
    }

    public function accessModule(User $user): bool
    {
        return $user->is_admin
            || $user->hasPermission('financial.receitas.view')
            || $user->hasPermission('financial.despesas.view')
            || $user->hasPermission('financial.categories.view')
            || $user->hasPermission('financial.accounts.view')
            || $user->hasPermission('financial.contacts.view')
            || $user->hasPermission('financial.cost-centers.view')
            || $user->hasPermission('financial.reports.view')
            || $user->hasPermission('financial.receitas.manage')
            || $user->hasPermission('financial.despesas.manage')
            || $user->hasPermission('financial.categories.manage')
            || $user->hasPermission('financial.accounts.manage')
            || $user->hasPermission('financial.contacts.manage')
            || $user->hasPermission('financial.cost-centers.manage')
            || $user->hasPermission('financial.reports.manage')
            || $user->hasPermission('financial.fechamento.view')
            || $user->hasPermission('financial.fechamento.generate')
            || $user->hasPermission('financial.campanhas.view')
            || $user->hasPermission('financial.campanhas.manage');
    }

    public function campaignAction(User $user, string $action): bool
    {
        return $user->is_admin
            || $user->hasPermission('financial.campanhas.manage')
            || $user->hasPermission("financial.campanhas.{$action}");
    }
}
