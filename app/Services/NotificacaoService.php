<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Member;
use App\Models\NotificacaoEnviada;
use App\Models\NotificacaoGrupo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

    /**
     * Envia para um número digitado manualmente (sem vínculo com membro).
     * O histórico usa apenas o telefone no destinatário.
     */
    public function enviarParaTelefone(string $telefoneBruto, string $mensagem): array
    {
        $phone = WhatsAppService::normalizarNumero($telefoneBruto);
        if (strlen($phone) < 12) {
            $resultado = ['success' => false, 'error' => 'Número inválido ou incompleto (use DDD + número).'];
            $this->registrarEnvio(null, $phone !== '' ? $phone : trim($telefoneBruto), $mensagem, $resultado);

            return $resultado;
        }

        $resultado = $this->whatsappService->enviarMensagem($phone, $mensagem);
        $this->registrarEnvio(null, $phone, $mensagem, $resultado);

        return $resultado;
    }

    /**
     * @param  list<string>  $telefonesBrutos  Um item por número (já separado por linha/vírgula no controller)
     * @return array{enviadas: int, erros: int, total: int}
     */
    public function enviarParaTelefonesManuais(array $telefonesBrutos, string $mensagem): array
    {
        $enviadas = 0;
        $erros = 0;
        foreach ($telefonesBrutos as $bruto) {
            if (! is_string($bruto) || trim($bruto) === '') {
                continue;
            }
            $r = $this->enviarParaTelefone($bruto, $mensagem);
            if ($r['success'] ?? false) {
                $enviadas++;
            } else {
                $erros++;
            }
        }

        return ['enviadas' => $enviadas, 'erros' => $erros, 'total' => $enviadas + $erros];
    }

    /**
     * Envia mídia para membros (imagem ou vídeo).
     *
     * @param iterable<Member> $members
     * @return array{enviadas: int, erros: int, total: int}
     */
    public function enviarMidiaParaMembros(iterable $members, UploadedFile $arquivo, string $tipoMidia, string $legenda = ''): array
    {
        $path = $arquivo->store('notificacoes/midias', 'public');
        $mediaUrl = url(Storage::disk('public')->url($path));
        $enviadas = 0;
        $erros = 0;
        foreach ($members as $member) {
            if (! $member instanceof Member) {
                continue;
            }
            $phone = $member->phone;
            if (empty($phone)) {
                $erros++;
                continue;
            }
            $resultado = $tipoMidia === 'video'
                ? $this->whatsappService->enviarVideo($phone, $mediaUrl, $legenda)
                : $this->whatsappService->enviarImagem($phone, $mediaUrl, $legenda);

            $this->registrarEnvio(
                $member->id,
                $phone,
                $legenda !== '' ? $legenda : '[Mídia enviada]',
                $resultado,
                $tipoMidia
            );

            if ($resultado['success'] ?? false) {
                $enviadas++;
            } else {
                $erros++;
            }
        }

        return ['enviadas' => $enviadas, 'erros' => $erros, 'total' => $enviadas + $erros];
    }

    private function registrarEnvio(?int $memberId, ?string $telefone, string $mensagem, array $resultado, string $tipoNotificacao = 'custom'): void
    {
        $status = ($resultado['success'] ?? false) ? 'enviada' : 'erro';
        NotificacaoEnviada::create([
            'member_id' => $memberId,
            'telefone' => $telefone,
            'tipo_notificacao' => $tipoNotificacao,
            'mensagem' => $mensagem,
            'data_envio' => now(),
            'status' => $status,
            'resposta_api' => $resultado['data'] ?? $resultado,
            'tentativas' => 1,
            'erro_detalhes' => ($resultado['error'] ?? null),
        ]);
    }
}
