<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Member;
use App\Models\NotificacaoEnviada;
use App\Models\NotificacaoGrupo;
use Illuminate\Http\UploadedFile;
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
    public function enviarMidiaParaMembros(iterable $members, UploadedFile $arquivo, ?string $tipoMidia = null, string $legenda = ''): array
    {
        $midia = $this->prepararMidiaParaEnvioUrl($arquivo, $tipoMidia);
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
            $resultado = $this->whatsappService->enviarMidiaPorUrl(
                $phone,
                $midia['url'],
                $midia['tipo'],
                $midia['mime'],
                $midia['file_name'],
                $legenda,
                true
            );

            $this->registrarEnvio(
                $member->id,
                $phone,
                $legenda !== '' ? $legenda : '[Mídia enviada]',
                $resultado,
                $midia['tipo']
            );

            if ($resultado['success'] ?? false) {
                $enviadas++;
            } else {
                $erros++;
            }
        }

        return ['enviadas' => $enviadas, 'erros' => $erros, 'total' => $enviadas + $erros];
    }

    /**
     * Envia mídia para todos os membros de um departamento.
     *
     * @return array{enviadas: int, erros: int, total: int}
     */
    public function enviarMidiaParaDepartamento(Department $department, UploadedFile $arquivo, ?string $tipoMidia = null, string $legenda = ''): array
    {
        $members = $department->members()->whereNotNull('members.phone')->where('members.phone', '!=', '')->get();
        return $this->enviarMidiaParaMembros($members, $arquivo, $tipoMidia, $legenda);
    }

    /**
     * Envia mídia para número manual único.
     *
     * @return array{success: bool, data?: array, error?: string, status?: int}
     */
    public function enviarMidiaParaTelefone(string $telefoneBruto, UploadedFile $arquivo, ?string $tipoMidia = null, string $legenda = ''): array
    {
        $midia = $this->prepararMidiaParaEnvioUrl($arquivo, $tipoMidia);
        $phone = WhatsAppService::normalizarNumero($telefoneBruto);
        if (strlen($phone) < 12) {
            $resultado = ['success' => false, 'error' => 'Número inválido ou incompleto (use DDD + número).'];
            $this->registrarEnvio(null, $phone !== '' ? $phone : trim($telefoneBruto), $legenda !== '' ? $legenda : '[Mídia enviada]', $resultado, $midia['tipo']);
            return $resultado;
        }

        $resultado = $this->whatsappService->enviarMidiaPorUrl(
            $phone,
            $midia['url'],
            $midia['tipo'],
            $midia['mime'],
            $midia['file_name'],
            $legenda,
            true
        );
        $this->registrarEnvio(null, $phone, $legenda !== '' ? $legenda : '[Mídia enviada]', $resultado, $midia['tipo']);
        return $resultado;
    }

    /**
     * Envia mídia para lista de números manuais.
     *
     * @param list<string> $telefonesBrutos
     * @return array{enviadas: int, erros: int, total: int}
     */
    public function enviarMidiaParaTelefonesManuais(array $telefonesBrutos, UploadedFile $arquivo, ?string $tipoMidia = null, string $legenda = ''): array
    {
        $enviadas = 0;
        $erros = 0;

        foreach ($telefonesBrutos as $bruto) {
            if (! is_string($bruto) || trim($bruto) === '') {
                continue;
            }
            $r = $this->enviarMidiaParaTelefone($bruto, $arquivo, $tipoMidia, $legenda);
            if ($r['success'] ?? false) {
                $enviadas++;
            } else {
                $erros++;
            }
        }

        return ['enviadas' => $enviadas, 'erros' => $erros, 'total' => $enviadas + $erros];
    }

    /**
     * @return array{url: string, tipo: string, mime: string, file_name: string}
     */
    private function prepararMidiaParaEnvioUrl(UploadedFile $arquivo, ?string $tipoInformado = null): array
    {
        $path = $arquivo->store('notificacoes/midias', 'public');
        $url = url(Storage::disk('public')->url($path));
        $fileName = $arquivo->getClientOriginalName();

        $mime = strtolower((string) $arquivo->getMimeType());
        $tipo = 'document';
        if (is_string($tipoInformado) && trim($tipoInformado) !== '') {
            $tipo = strtolower(trim($tipoInformado));
        } elseif (str_starts_with($mime, 'image/')) {
            $tipo = 'image';
        } elseif (str_starts_with($mime, 'video/')) {
            $tipo = 'video';
        } elseif (str_starts_with($mime, 'audio/')) {
            $tipo = 'audio';
        }

        $mimeParaApi = $mime;
        if ($tipo === 'document' && $mime === 'application/pdf') {
            $mimeParaApi = 'document/pdf';
        }

        if ($mimeParaApi === '') {
            $mimeParaApi = $tipo === 'image'
                ? 'image/png'
                : ($tipo === 'video'
                    ? 'video/mp4'
                    : ($tipo === 'audio' ? 'audio/mpeg' : 'document/pdf'));
        }

        return [
            'url' => $url,
            'tipo' => $tipo,
            'mime' => $mimeParaApi,
            'file_name' => $fileName,
        ];
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
