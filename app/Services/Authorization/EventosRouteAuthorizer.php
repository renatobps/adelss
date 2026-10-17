<?php

namespace App\Services\Authorization;

use App\Models\User;
use App\Policies\Eventos\CampaignPolicy;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Autorização do módulo Gestor de Eventos (campanhas de arrecadação e gastos).
 * Mapeia cada rota para a ação eventos.campanhas.{view|create|edit|delete|pagar|estornar}.
 */
class EventosRouteAuthorizer
{
    public function __construct(
        private CampaignPolicy $campaignPolicy,
    ) {}

    /**
     * @param  callable(string): Response  $denyAccess
     */
    public function authorize(Request $request, User $user, callable $denyAccess): ?Response
    {
        if ($user->is_admin) {
            return null;
        }

        $routeName = $request->route()?->getName() ?? '';

        $action = match ($routeName) {
            'eventos.campaigns.create',
            'eventos.campaigns.store',
            'eventos.campaigns.sponsors.store',
            'eventos.campaigns.expenses.store' => 'create',
            'eventos.campaigns.edit',
            'eventos.campaigns.update',
            'eventos.campaigns.sponsors.update',
            'eventos.campaigns.expenses.update',
            'eventos.campaigns.reminders.global.update',
            'eventos.campaigns.reminders.update',
            'eventos.campaigns.reminders.toggle-sponsor' => 'edit',
            'eventos.campaigns.destroy',
            'eventos.campaigns.sponsors.destroy',
            'eventos.campaigns.expenses.destroy' => 'delete',
            'eventos.campaigns.installments.pay',
            'eventos.campaigns.installments.pay-batch',
            'eventos.campaigns.installments.resend',
            'eventos.campaigns.sponsors.charge-overdue',
            'eventos.campaigns.reminders.send-now',
            'eventos.campaigns.reminders.test' => 'pagar',
            'eventos.campaigns.installments.reverse' => 'estornar',
            default => 'view',
        };

        $labels = [
            'view' => 'visualizar campanhas e gastos de eventos',
            'create' => 'criar campanhas, adicionar patrocinadores ou registrar gastos',
            'edit' => 'editar campanhas ou gastos',
            'delete' => 'excluir registros de campanhas ou gastos',
            'pagar' => 'registrar pagamentos e cobranças de campanhas',
            'estornar' => 'estornar pagamentos de campanhas',
        ];

        return $this->campaignPolicy->action($user, $action)
            ? null
            : $denyAccess("Acesso negado. Você não tem permissão para {$labels[$action]}.");
    }
}
