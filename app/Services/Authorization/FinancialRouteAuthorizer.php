<?php

namespace App\Services\Authorization;

use App\Models\FinancialAccount;
use App\Models\FinancialCategory;
use App\Models\FinancialContact;
use App\Models\FinancialCostCenter;
use App\Models\FinancialTransaction;
use App\Models\User;
use App\Policies\Financial\FinancialAccountPolicy;
use App\Policies\Financial\FinancialCategoryPolicy;
use App\Policies\Financial\FinancialContactPolicy;
use App\Policies\Financial\FinancialCostCenterPolicy;
use App\Policies\Financial\FinancialModulePolicy;
use App\Policies\Financial\FinancialTransactionPolicy;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FinancialRouteAuthorizer
{
    public function __construct(
        private FinancialModulePolicy $modulePolicy,
        private FinancialTransactionPolicy $transactionPolicy,
        private FinancialCategoryPolicy $categoryPolicy,
        private FinancialAccountPolicy $accountPolicy,
        private FinancialContactPolicy $contactPolicy,
        private FinancialCostCenterPolicy $costCenterPolicy,
    ) {}

    /**
     * @param  callable(string): Response  $denyAccess
     */
    public function authorize(Request $request, User $user, callable $denyAccess): ?Response
    {
        if ($user->is_admin) {
            return null;
        }

        $routeName = $request->route()?->getName();
        $action = $request->route()?->getActionMethod();

        if (str_starts_with($routeName ?? '', 'financial.summary')) {
            return $this->modulePolicy->viewSummary($user)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para visualizar o resumo financeiro.');
        }

        if (str_starts_with($routeName ?? '', 'financial.automations')) {
            if (in_array($routeName, [
                'financial.automations.toggle',
                'financial.automations.update',
                'financial.automations.test-treasurer',
                'financial.automations.send-smart-summary',
            ], true)) {
                return $this->modulePolicy->manageAutomations($user)
                    ? null
                    : $denyAccess('Acesso negado. Você não tem permissão para gerenciar automações.');
            }

            return $this->modulePolicy->viewAutomations($user)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para visualizar automações.');
        }

        if (str_starts_with($routeName ?? '', 'financial.fixed-expenses')) {
            if ($routeName === 'financial.fixed-expenses.index') {
                return $this->modulePolicy->viewFixedExpenses($user)
                    ? null
                    : $denyAccess('Acesso negado. Você não tem permissão para visualizar despesas fixas.');
            }

            return $this->modulePolicy->manageFixedExpenses($user)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para gerenciar despesas fixas.');
        }

        if (str_starts_with($routeName ?? '', 'financial.transactions')) {
            return $this->authorizeTransactionRoute($request, $user, $routeName, $action, $denyAccess);
        }

        if (str_starts_with($routeName ?? '', 'financial.checkout')) {
            return $this->transactionPolicy->checkout($user)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para gerar cobranças.');
        }

        if (str_starts_with($routeName ?? '', 'financial.reports')) {
            return $this->modulePolicy->viewReports($user)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para visualizar relatórios.');
        }

        if (str_starts_with($routeName ?? '', 'financial.categories')) {
            return $this->authorizeResourceRoute(
                $user,
                $routeName,
                $action,
                $this->categoryPolicy,
                'categorias',
                $denyAccess
            );
        }

        if (str_starts_with($routeName ?? '', 'financial.accounts')) {
            return $this->authorizeResourceRoute(
                $user,
                $routeName,
                $action,
                $this->accountPolicy,
                'contas',
                $denyAccess
            );
        }

        if (str_starts_with($routeName ?? '', 'financial.contacts')) {
            if ($routeName === 'financial.contacts.categories.store') {
                return $this->contactPolicy->create($user)
                    ? null
                    : $denyAccess('Acesso negado. Você não tem permissão para adicionar contatos.');
            }

            return $this->authorizeResourceRoute(
                $user,
                $routeName,
                $action,
                $this->contactPolicy,
                'contatos',
                $denyAccess
            );
        }

        if (str_starts_with($routeName ?? '', 'financial.campaigns')) {
            return $this->authorizeCampaignRoute($user, $routeName, $denyAccess);
        }

        if (str_starts_with($routeName ?? '', 'financial.cost-centers')) {
            return $this->authorizeResourceRoute(
                $user,
                $routeName,
                $action,
                $this->costCenterPolicy,
                'centros de custo',
                $denyAccess
            );
        }

        return $this->modulePolicy->accessModule($user)
            ? null
            : $denyAccess('Acesso negado. Você não tem permissão para acessar este módulo.');
    }

    /**
     * Campanhas de arrecadação: mapeia cada rota para a ação
     * financial.campanhas.{view|create|edit|delete|pagar|estornar}.
     *
     * @param  callable(string): Response  $denyAccess
     */
    private function authorizeCampaignRoute(User $user, ?string $routeName, callable $denyAccess): ?Response
    {
        $action = match ($routeName) {
            'financial.campaigns.create',
            'financial.campaigns.store',
            'financial.campaigns.sponsors.store' => 'create',
            'financial.campaigns.edit',
            'financial.campaigns.update',
            'financial.campaigns.sponsors.update' => 'edit',
            'financial.campaigns.destroy',
            'financial.campaigns.sponsors.destroy' => 'delete',
            'financial.campaigns.installments.pay',
            'financial.campaigns.installments.pay-batch',
            'financial.campaigns.installments.resend' => 'pagar',
            'financial.campaigns.installments.reverse' => 'estornar',
            default => 'view',
        };

        $labels = [
            'view' => 'visualizar campanhas',
            'create' => 'criar campanhas ou adicionar patrocinadores',
            'edit' => 'editar campanhas',
            'delete' => 'excluir registros de campanhas',
            'pagar' => 'registrar pagamentos de campanhas',
            'estornar' => 'estornar pagamentos de campanhas',
        ];

        return $this->modulePolicy->campaignAction($user, $action)
            ? null
            : $denyAccess("Acesso negado. Você não tem permissão para {$labels[$action]}.");
    }

    /**
     * @param  callable(string): Response  $denyAccess
     */
    private function authorizeTransactionRoute(
        Request $request,
        User $user,
        ?string $routeName,
        string $action,
        callable $denyAccess
    ): ?Response {
        $transaction = $this->resolveTransaction($request);

        if (in_array($routeName, ['financial.transactions.index'], true) || $action === 'index') {
            return $this->transactionPolicy->viewAny($user)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para visualizar transações.');
        }

        if (in_array($routeName, ['financial.transactions.show'], true) || $action === 'show') {
            return $transaction && $this->transactionPolicy->view($user, $transaction)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para visualizar transações.');
        }

        if ($routeName === 'financial.transactions.store.receita' || $action === 'storeReceita') {
            return $this->transactionPolicy->createReceita($user)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para adicionar receitas.');
        }

        if ($routeName === 'financial.transactions.store.despesa' || $action === 'storeDespesa') {
            return $this->transactionPolicy->createDespesa($user)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para adicionar despesas.');
        }

        if (in_array($routeName, ['financial.transactions.edit', 'financial.transactions.update'], true)
            || in_array($action, ['edit', 'update', 'updateDescription'], true)) {
            return $transaction && $this->transactionPolicy->update($user, $transaction)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para editar esta transação.');
        }

        if ($routeName === 'financial.transactions.destroy' || $action === 'destroy') {
            return $transaction && $this->transactionPolicy->delete($user, $transaction)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para excluir esta transação.');
        }

        if ($routeName === 'financial.transactions.export' || $action === 'export') {
            return $this->transactionPolicy->export($user)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para exportar transações.');
        }

        if ($routeName === 'financial.transactions.import' || $action === 'import') {
            return $this->transactionPolicy->import($user)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para importar transações.');
        }

        if ($routeName === 'financial.transactions.receipt' || $action === 'receipt') {
            return $transaction && $this->transactionPolicy->receipt($user, $transaction)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para visualizar recibos.');
        }

        if ($routeName === 'financial.transactions.send-receipt' || $action === 'sendReceiptWhatsApp') {
            return $transaction && $this->transactionPolicy->sendReceipt($user, $transaction)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para enviar comprovantes.');
        }

        if ($routeName === 'financial.transactions.duplicate' || $action === 'duplicate') {
            return $transaction && $this->transactionPolicy->duplicate($user, $transaction)
                ? null
                : $denyAccess('Acesso negado. Você não tem permissão para duplicar esta transação.');
        }

        return $this->transactionPolicy->viewAny($user)
            ? null
            : $denyAccess('Acesso negado. Você não tem permissão para acessar transações.');
    }

    /**
     * @param  FinancialCategoryPolicy|FinancialAccountPolicy|FinancialContactPolicy|FinancialCostCenterPolicy  $policy
     * @param  callable(string): Response  $denyAccess
     */
    private function authorizeResourceRoute(
        User $user,
        ?string $routeName,
        string $action,
        object $policy,
        string $resourceLabel,
        callable $denyAccess
    ): ?Response {
        if (!method_exists($policy, 'authorizeRouteAction')) {
            return $denyAccess("Acesso negado. Você não tem permissão para acessar {$resourceLabel}.");
        }

        return $policy->authorizeRouteAction($user, $action)
            ? null
            : $denyAccess("Acesso negado. Você não tem permissão para acessar {$resourceLabel}.");
    }

    private function resolveTransaction(Request $request): ?FinancialTransaction
    {
        $transaction = $request->route('transaction');

        if ($transaction instanceof FinancialTransaction) {
            return $transaction;
        }

        if ($transaction) {
            return FinancialTransaction::find($transaction);
        }

        return null;
    }
}
