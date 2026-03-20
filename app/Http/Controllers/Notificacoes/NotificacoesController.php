<?php

namespace App\Http\Controllers\Notificacoes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Controller do módulo Notificações (WhatsApp).
 * Integração com NotifiADel: grupos, enquetes, painel de envio, configuração, templates.
 * Utiliza os membros (Member) já existentes no sistema.
 */
class NotificacoesController extends Controller
{
    /**
     * Grupos de membros para envio de notificações.
     */
    public function grupos()
    {
        return view('notificacoes.grupos.index');
    }

    /**
     * Enquetes via WhatsApp.
     */
    public function enquetes()
    {
        return view('notificacoes.enquetes.index');
    }

    /**
     * Painel de envio de notificações.
     */
    public function painel()
    {
        return view('notificacoes.painel.index');
    }

    /**
     * Configuração WhatsApp (conexão / PAO).
     */
    public function config()
    {
        return view('notificacoes.config.index');
    }

    /**
     * Templates de mensagens.
     */
    public function templates()
    {
        return view('notificacoes.templates.index');
    }
}
