<?php

namespace App\Http\Controllers\Notificacoes;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Member;
use App\Models\NotificacaoEnviada;
use App\Services\NotificacaoService;
use Illuminate\Http\Request;

class PainelController extends Controller
{
    public function index(Request $request)
    {
        $query = NotificacaoEnviada::query()->with('member:id,name,phone');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('data_inicio')) {
            $query->whereDate('data_envio', '>=', $request->data_inicio);
        }
        if ($request->filled('data_fim')) {
            $query->whereDate('data_envio', '<=', $request->data_fim);
        }

        $notificacoes = $query->orderByDesc('data_envio')->paginate(15);

        $ultimoMes = now()->subDays(30);
        $stats = [
            'enviadas' => NotificacaoEnviada::where('status', 'enviada')->where('data_envio', '>=', $ultimoMes)->count(),
            'erros' => NotificacaoEnviada::where('status', 'erro')->where('data_envio', '>=', $ultimoMes)->count(),
            'total' => NotificacaoEnviada::where('data_envio', '>=', $ultimoMes)->count(),
        ];

        $members = Member::active()->whereNotNull('phone')->where('phone', '!=', '')->orderBy('name')->get(['id', 'name', 'phone']);
        $departments = Department::active()->orderBy('name')->get(['id', 'name']);

        return view('notificacoes.painel.index', compact('notificacoes', 'stats', 'members', 'departments'));
    }

    public function enviar(Request $request)
    {
        $request->validate([
            'mensagem' => 'required|string|max:4096',
            'members' => 'nullable|array',
            'members.*' => 'integer|exists:members,id',
            'departments' => 'nullable|array',
            'departments.*' => 'integer|exists:departments,id',
        ]);

        $mensagem = $request->mensagem;
        $memberIds = $request->input('members', []);
        $departmentIds = $request->input('departments', []);

        if (empty($memberIds) && empty($departmentIds)) {
            return back()->withErrors(['destinatarios' => 'Selecione pelo menos um membro ou departamento.'])->withInput();
        }

        $service = app(NotificacaoService::class);
        $enviadas = 0;
        $erros = 0;

        if (!empty($memberIds)) {
            $members = Member::whereIn('id', $memberIds)->get();
            $r = $service->enviarParaMembros($members, $mensagem);
            $enviadas += $r['enviadas'];
            $erros += $r['erros'];
        }
        if (!empty($departmentIds)) {
            foreach (Department::whereIn('id', $departmentIds)->get() as $department) {
                $r = $service->enviarParaDepartamento($department, $mensagem);
                $enviadas += $r['enviadas'];
                $erros += $r['erros'];
            }
        }

        return back()->with('success', "Envio concluído: {$enviadas} enviadas, {$erros} erros.");
    }
}
