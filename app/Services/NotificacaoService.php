<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Member;
use App\Models\NotificacaoEnviada;
use App\Models\NotificacaoGrupo;
use Illuminate\Support\Facades\Log;

class NotificacaoService
{
    public function __construct(
        private WhatsAppService $whatsappService
    ) {}

    /**
     * Envia mensagem para um membro (usa Member->phone).
     */
    public function enviarParaMembro(Member $member, string $mensagem): array
    {
        $phone = $member->phone;
        if (empty($phone)) {
            return ['success' => false, 'error' => 'Membro sem telefone cadastrado.'];
        }
        $resultado = $this->whatsappService->enviarMensagem($phone, $mensagem);
        $this->registrarEnvio($member->id, $phone, $mensagem, $resultado);
        return $resultado;
    }

    /**
     * Envia mensagem para vários membros (ex.: um grupo).
     * @param iterable<Member> $members
     * @return array{enviadas: int, erros: int, total: int}
     */
    public function enviarParaMembros(iterable $members, string $mensagem): array
    {
        $enviadas = 0;
        $erros = 0;
        foreach ($members as $member) {
            if (!$member instanceof Member) {
                continue;
            }
            $r = $this->enviarParaMembro($member, $mensagem);
            if ($r['success'] ?? false) {
                $enviadas++;
            } else {
                $erros++;
            }
        }
        return ['enviadas' => $enviadas, 'erros' => $erros, 'total' => $enviadas + $erros];
    }

    /**
     * Envia mensagem para todos os membros de um grupo (NotificacaoGrupo).
     */
    public function enviarParaGrupo(NotificacaoGrupo $grupo, string $mensagem): array
    {
        $members = $grupo->members()->get();
        return $this->enviarParaMembros($members, $mensagem);
    }

    /**
     * Envia mensagem para todos os membros de um departamento.
     * Usa os membros vinculados ao departamento (department_members) e com telefone.
     */
    public function enviarParaDepartamento(Department $department, string $mensagem): array
    {
        $members = $department->members()->whereNotNull('members.phone')->where('members.phone', '!=', '')->get();
        return $this->enviarParaMembros($members, $mensagem);
    }

    private function registrarEnvio(?int $memberId, ?string $telefone, string $mensagem, array $resultado): void
    {
        $status = ($resultado['success'] ?? false) ? 'enviada' : 'erro';
        NotificacaoEnviada::create([
            'member_id' => $memberId,
            'telefone' => $telefone,
            'tipo_notificacao' => 'custom',
            'mensagem' => $mensagem,
            'data_envio' => now(),
            'status' => $status,
            'resposta_api' => $resultado['data'] ?? $resultado,
            'tentativas' => 1,
            'erro_detalhes' => ($resultado['error'] ?? null),
        ]);
    }
}
