<?php

namespace App\Policies\Financial;

use App\Models\FinancialTransaction;
use App\Models\User;

class FinancialTransactionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_admin
            || $user->hasPermission('financial.receitas.view')
            || $user->hasPermission('financial.despesas.view')
            || $user->hasPermission('financial.receitas.manage')
            || $user->hasPermission('financial.despesas.manage');
    }

    public function view(User $user, FinancialTransaction $transaction): bool
    {
        return $this->canAccessTransactionType($user, $transaction, 'view');
    }

    public function createReceita(User $user): bool
    {
        return $user->is_admin
            || $user->hasPermission('financial.receitas.create')
            || $user->hasPermission('financial.receitas.manage');
    }

    public function createDespesa(User $user): bool
    {
        return $user->is_admin
            || $user->hasPermission('financial.despesas.create')
            || $user->hasPermission('financial.despesas.manage');
    }

    public function update(User $user, FinancialTransaction $transaction): bool
    {
        return $this->canAccessTransactionType($user, $transaction, 'edit');
    }

    public function delete(User $user, FinancialTransaction $transaction): bool
    {
        return $this->canAccessTransactionType($user, $transaction, 'delete');
    }

    public function export(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function import(User $user): bool
    {
        return $user->is_admin
            || $user->hasPermission('financial.receitas.create')
            || $user->hasPermission('financial.despesas.create')
            || $user->hasPermission('financial.receitas.manage')
            || $user->hasPermission('financial.despesas.manage');
    }

    public function receipt(User $user, FinancialTransaction $transaction): bool
    {
        return $this->view($user, $transaction);
    }

    public function sendReceipt(User $user, FinancialTransaction $transaction): bool
    {
        if ($transaction->type === 'receita') {
            return $user->is_admin
                || $user->hasPermission('financial.receitas.view')
                || $user->hasPermission('financial.receitas.manage');
        }

        if ($transaction->type === 'despesa') {
            return $user->is_admin
                || $user->hasPermission('financial.despesas.view')
                || $user->hasPermission('financial.despesas.manage');
        }

        return false;
    }

    public function duplicate(User $user, FinancialTransaction $transaction): bool
    {
        return $this->canAccessTransactionType($user, $transaction, 'create');
    }

    public function checkout(User $user): bool
    {
        return $this->createReceita($user);
    }

    private function canAccessTransactionType(User $user, FinancialTransaction $transaction, string $action): bool
    {
        if ($user->is_admin) {
            return true;
        }

        $prefix = $transaction->type === 'receita' ? 'financial.receitas' : 'financial.despesas';

        return $user->hasPermission("{$prefix}.{$action}")
            || $user->hasPermission("{$prefix}.manage");
    }
}
