<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Member;
use App\Models\NotificacaoEnviada;
use App\Models\NotificacaoGrupo;
use Illuminate\Http\UploadedFile;

class NotificacaoService
{
    public function __construct(
        private WhatsAppService $whatsappService
    ) {}

    /**
     * Envia mensagem para um membro (usa Member->phone).
     * Variáveis: {nome} → nome completo do membro.
     */
    public function enviarParaMembro(Member $member, string $mensagem): array
    {
        $phone = $member->phone;
        if (empty($phone)) {
            return ['success' => false, 'error' => 'Membro sem telefone cadastrado.'];
        }
        $texto = $this->personalizarMensagem($mensagem, $member->name);
        $resultado = $this->whatsappService->enviarMensagem($phone, $texto);
        $this->registrarEnvio($member->id, $phone, $texto, $resultado);
        return $resultado;
    }

    /**
     * Envia mensagem para vários membros (ex.: um grupo).
     * @param iterable<Member> $members
     * @return array{enviadas: int, erros: int, total: int, detalhes_erros: list<string>}
     */
    public function enviarParaMembros(iterable $members, string $mensagem): array
    {
        $enviadas = 0;
        $erros = 0;
        $detalhesErros = [];
        foreach ($members as $member) {
            if (!$member instanceof Member) {
                continue;
            }
            $r = $this->enviarParaMembro($member, $mensagem);
            if ($r['success'] ?? false) {
                $enviadas++;
            } else {
                $erros++;
                $detalhesErros[] = $this->formatarDetalheErro($member->name ?? $member->phone, $r['error'] ?? null);
            }
        }
        return ['enviadas' => $enviadas, 'erros' => $erros, 'total' => $enviadas + $erros, 'detalhes_erros' => $detalhesErros];
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

        $membro = $this->encontrarMembroPorTelefone($phone);
        $texto = $this->personalizarMensagem($mensagem, $membro?->name);
        $resultado = $this->whatsappService->enviarMensagem($phone, $texto);
        $this->registrarEnvio($membro?->id, $phone, $texto, $resultado);

        return $resultado;
    }

    /**
     * @param  list<string>  $telefonesBrutos  Um item por número (já separado por linha/vírgula no controller)
     * @return array{enviadas: int, erros: int, total: int, detalhes_erros: list<string>}
     */
    public function enviarParaTelefonesManuais(array $telefonesBrutos, string $mensagem): array
    {
        $enviadas = 0;
        $erros = 0;
        $detalhesErros = [];
        foreach ($telefonesBrutos as $bruto) {
            if (! is_string($bruto) || trim($bruto) === '') {
                continue;
            }
            $r = $this->enviarParaTelefone($bruto, $mensagem);
            if ($r['success'] ?? false) {
                $enviadas++;
            } else {
                $erros++;
                $detalhesErros[] = $this->formatarDetalheErro($bruto, $r['error'] ?? null);
            }
        }

        return ['enviadas' => $enviadas, 'erros' => $erros, 'total' => $enviadas + $erros, 'detalhes_erros' => $detalhesErros];
    }

    /**
     * Envia mídia para membros (imagem ou vídeo).
     *
     * @param iterable<Member> $members
     * @return array{enviadas: int, erros: int, total: int, detalhes_erros: list<string>}
     */
    public function enviarMidiaParaMembros(iterable $members, UploadedFile $arquivo, ?string $tipoMidia = null, string $legenda = ''): array
    {
        $midia = $this->detectarMidiaArquivo($arquivo, $tipoMidia);
        $enviadas = 0;
        $erros = 0;
        $detalhesErros = [];
        foreach ($members as $member) {
            if (! $member instanceof Member) {
                continue;
            }
            $phone = $member->phone;
            if (empty($phone)) {
                $erros++;
                $detalhesErros[] = $this->formatarDetalheErro($member->name ?? 'membro', 'Membro sem telefone cadastrado.');
                continue;
            }
            $legendaPersonalizada = $this->personalizarMensagem($legenda, $member->name);
            $resultado = $this->whatsappService->enviarMidiaArquivo(
                $phone,
                $arquivo,
                $midia['tipo'],
                $midia['is_pdf_document'],
                $midia['file_name'],
                $legendaPersonalizada
            );

            $this->registrarEnvio(
                $member->id,
                $phone,
                $legendaPersonalizada !== '' ? $legendaPersonalizada : '[Mídia enviada]',
                $resultado,
                $midia['tipo']
            );

            if ($resultado['success'] ?? false) {
                $enviadas++;
            } else {
                $erros++;
                $detalhesErros[] = $this->formatarDetalheErro($member->name ?? $phone, $resultado['error'] ?? null);
            }
        }

        return ['enviadas' => $enviadas, 'erros' => $erros, 'total' => $enviadas + $erros, 'detalhes_erros' => $detalhesErros];
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
        $midia = $this->detectarMidiaArquivo($arquivo, $tipoMidia);
        $phone = WhatsAppService::normalizarNumero($telefoneBruto);
        if (strlen($phone) < 12) {
            $resultado = ['success' => false, 'error' => 'Número inválido ou incompleto (use DDD + número).'];
            $this->registrarEnvio(null, $phone !== '' ? $phone : trim($telefoneBruto), $legenda !== '' ? $legenda : '[Mídia enviada]', $resultado, $midia['tipo']);
            return $resultado;
        }

        $membro = $this->encontrarMembroPorTelefone($phone);
        $legendaPersonalizada = $this->personalizarMensagem($legenda, $membro?->name);
        $resultado = $this->whatsappService->enviarMidiaArquivo(
            $phone,
            $arquivo,
            $midia['tipo'],
            $midia['is_pdf_document'],
            $midia['file_name'],
            $legendaPersonalizada
        );
        $this->registrarEnvio(
            $membro?->id,
            $phone,
            $legendaPersonalizada !== '' ? $legendaPersonalizada : '[Mídia enviada]',
            $resultado,
            $midia['tipo']
        );
        return $resultado;
    }

    /**
     * Envia mídia para lista de números manuais.
     *
     * @param list<string> $telefonesBrutos
     * @return array{enviadas: int, erros: int, total: int, detalhes_erros: list<string>}
     */
    public function enviarMidiaParaTelefonesManuais(array $telefonesBrutos, UploadedFile $arquivo, ?string $tipoMidia = null, string $legenda = ''): array
    {
        $enviadas = 0;
        $erros = 0;
        $detalhesErros = [];

        foreach ($telefonesBrutos as $bruto) {
            if (! is_string($bruto) || trim($bruto) === '') {
                continue;
            }
            $r = $this->enviarMidiaParaTelefone($bruto, $arquivo, $tipoMidia, $legenda);
            if ($r['success'] ?? false) {
                $enviadas++;
            } else {
                $erros++;
                $detalhesErros[] = $this->formatarDetalheErro($bruto, $r['error'] ?? null);
            }
        }

        return ['enviadas' => $enviadas, 'erros' => $erros, 'total' => $enviadas + $erros, 'detalhes_erros' => $detalhesErros];
    }

    /**
     * Substitui variáveis da mensagem.
     * {nome} e {primeiro_nome} → somente o primeiro nome do destinatário.
     */
    public function personalizarMensagem(string $mensagem, ?string $nome): string
    {
        $nomeCompleto = trim((string) $nome);
        $primeiroNome = $nomeCompleto !== ''
            ? (explode(' ', preg_replace('/\s+/', ' ', $nomeCompleto) ?? $nomeCompleto)[0] ?: $nomeCompleto)
            : 'irmão(ã)';

        return str_ireplace(
            ['{nome}', '{primeiro_nome}'],
            [$primeiroNome, $primeiroNome],
            $mensagem
        );
    }

    private function encontrarMembroPorTelefone(string $phoneNormalizado): ?Member
    {
        if ($phoneNormalizado === '') {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phoneNormalizado) ?? '';
        if (strlen($digits) < 10) {
            return null;
        }

        // Compara pelos últimos 10–11 dígitos (ignora variações de 55 / nono dígito).
        $sufixo = substr($digits, -11);

        return Member::query()
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->where(function ($q) use ($digits, $sufixo) {
                $q->where('phone', $digits)
                    ->orWhere('phone', 'like', '%' . $sufixo)
                    ->orWhereRaw(
                        "REPLACE(REPLACE(REPLACE(REPLACE(phone, ' ', ''), '-', ''), '(', ''), ')', '') LIKE ?",
                        ['%' . $sufixo]
                    );
            })
            ->first(['id', 'name', 'phone']);
    }

    /**
     * @return array{tipo: string, is_pdf_document: bool, file_name: string}
     */
    private function detectarMidiaArquivo(UploadedFile $arquivo, ?string $tipoInformado = null): array
    {
        $fileName = $arquivo->getClientOriginalName();
        $mime = strtolower((string) $arquivo->getMimeType());
        $tipo = $this->normalizarTipoMidia($tipoInformado, $mime);
        $isPdfDocumento = $tipo === 'document' && $mime === 'application/pdf';

        return [
            'tipo' => $tipo,
            'is_pdf_document' => $isPdfDocumento,
            'file_name' => $fileName,
        ];
    }

    private function normalizarTipoMidia(?string $tipoInformado, string $mime): string
    {
        $aliases = [
            'imagem' => 'image',
            'image' => 'image',
            'video' => 'video',
            'vídeo' => 'video',
            'audio' => 'audio',
            'document' => 'document',
            'documento' => 'document',
        ];

        if (is_string($tipoInformado) && trim($tipoInformado) !== '') {
            $informado = strtolower(trim($tipoInformado));
            if (isset($aliases[$informado])) {
                return $aliases[$informado];
            }
        }

        if (str_starts_with($mime, 'image/')) {
            return 'image';
        }
        if (str_starts_with($mime, 'video/')) {
            return 'video';
        }
        if (str_starts_with($mime, 'audio/')) {
            return 'audio';
        }

        return 'document';
    }

    private function registrarEnvio(?int $memberId, ?string $telefone, string $mensagem, array $resultado, string $tipoNotificacao = 'custom'): void
    {
        $sucesso = (bool) ($resultado['success'] ?? false);
        $payload = $resultado['data'] ?? $resultado;

        NotificacaoEnviada::create([
            'member_id' => $memberId,
            'telefone' => $telefone,
            'tipo_notificacao' => $tipoNotificacao,
            'mensagem' => $mensagem,
            'data_envio' => now(),
            'status' => $sucesso ? NotificacaoEnviada::STATUS_ENVIADA : NotificacaoEnviada::STATUS_ERRO,
            'whatsapp_message_id' => $sucesso ? $this->extrairWhatsappMessageId($payload) : null,
            'resposta_api' => $payload,
            'tentativas' => 1,
            'erro_detalhes' => $this->humanizarErro($resultado['error'] ?? null),
        ]);
    }

    /**
     * Processa webhook Receipt (READ_RECEIPT) da Evolution GO.
     * state: Delivered → Recebido | Read / ReadSelf → Lida
     */
    public function processarConfirmacaoEntrega(array $payload): bool
    {
        $event = strtolower((string) ($payload['event'] ?? ''));
        if (!in_array($event, ['receipt', 'read_receipt', 'messages.update', 'messages_update'], true)
            && !str_contains($event, 'receipt')) {
            // Continua se houver MessageIDs + state típicos de Receipt
            if (blank(data_get($payload, 'data.MessageIDs')) && blank(data_get($payload, 'data.key.id'))) {
                return false;
            }
        }

        $state = strtolower((string) (
            $payload['state']
            ?? data_get($payload, 'data.state')
            ?? data_get($payload, 'data.status')
            ?? ''
        ));

        $messageIds = data_get($payload, 'data.MessageIDs')
            ?? data_get($payload, 'data.messageIds')
            ?? data_get($payload, 'data.ids')
            ?? [];

        if (!is_array($messageIds) || $messageIds === []) {
            $single = data_get($payload, 'data.key.id')
                ?? data_get($payload, 'data.Info.ID')
                ?? data_get($payload, 'data.id');
            $messageIds = $single ? [(string) $single] : [];
        }

        $messageIds = array_values(array_filter(array_map('strval', $messageIds)));
        if ($messageIds === []) {
            return false;
        }

        $novoStatus = null;
        $campoData = null;
        if (in_array($state, ['delivered', 'entregue', 'server_ack', 'delivery_ack', '2'], true)
            || str_contains($state, 'deliver')) {
            $novoStatus = NotificacaoEnviada::STATUS_ENTREGUE;
            $campoData = 'recebido_em';
        } elseif (in_array($state, ['read', 'readself', 'lida', 'played', '3', '4'], true)
            || str_contains($state, 'read')) {
            $novoStatus = NotificacaoEnviada::STATUS_LIDA;
            $campoData = 'lido_em';
        } else {
            return false;
        }

        $quando = data_get($payload, 'data.Timestamp') ?? data_get($payload, 'data.timestamp');
        try {
            $quando = $quando ? \Carbon\Carbon::parse($quando) : now();
        } catch (\Throwable) {
            $quando = now();
        }

        $atualizadas = 0;
        $regs = NotificacaoEnviada::query()
            ->whereIn('whatsapp_message_id', $messageIds)
            ->whereIn('status', [
                NotificacaoEnviada::STATUS_ENVIADA,
                NotificacaoEnviada::STATUS_ENTREGUE,
                NotificacaoEnviada::STATUS_LIDA,
            ])
            ->get();

        foreach ($regs as $reg) {
            // Não regride: lida > entregue > enviada
            if ($reg->status === NotificacaoEnviada::STATUS_LIDA) {
                continue;
            }
            if ($novoStatus === NotificacaoEnviada::STATUS_ENTREGUE
                && $reg->status === NotificacaoEnviada::STATUS_ENTREGUE) {
                continue;
            }

            $update = ['status' => $novoStatus];
            if ($campoData && empty($reg->{$campoData})) {
                $update[$campoData] = $quando;
            }
            // Ao marcar lida, garante recebido_em
            if ($novoStatus === NotificacaoEnviada::STATUS_LIDA && empty($reg->recebido_em)) {
                $update['recebido_em'] = $quando;
            }

            $reg->update($update);
            $atualizadas++;
        }

        return $atualizadas > 0;
    }

    /**
     * @param  array<string, mixed>|mixed  $payload
     */
    private function extrairWhatsappMessageId(mixed $payload): ?string
    {
        if (!is_array($payload)) {
            return null;
        }

        $candidatos = [
            data_get($payload, 'data.Info.ID'),
            data_get($payload, 'data.Info.Id'),
            data_get($payload, 'Info.ID'),
            data_get($payload, 'key.id'),
            data_get($payload, 'data.key.id'),
            data_get($payload, 'data.ID'),
            data_get($payload, 'data.id'),
            data_get($payload, 'messageId'),
            data_get($payload, 'id'),
        ];

        foreach ($candidatos as $id) {
            if (is_string($id) && trim($id) !== '') {
                return trim($id);
            }
        }

        return null;
    }

    private function formatarDetalheErro(?string $destinatario, ?string $erro): string
    {
        $motivo = $this->humanizarErro($erro) ?? 'Falha desconhecida no envio.';
        $destinatario = trim((string) $destinatario);

        return $destinatario !== '' ? "{$destinatario}: {$motivo}" : $motivo;
    }

    private function humanizarErro(?string $erro): ?string
    {
        if ($erro === null || trim($erro) === '') {
            return null;
        }

        $erro = trim($erro);
        if (stripos($erro, 'not registered on WhatsApp') !== false) {
            return 'Número não está registrado no WhatsApp (verifique DDD + número).';
        }
        if (stripos($erro, 'invalid base64') !== false) {
            return 'Arquivo de mídia inválido ou corrompido (base64).';
        }
        if (stripos($erro, 'ECONNREFUSED') !== false) {
            return 'A API do WhatsApp não conseguiu acessar o arquivo de mídia.';
        }

        return $erro;
    }
}
