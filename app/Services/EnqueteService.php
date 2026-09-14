<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Enquete;
use App\Models\EnqueteEnvio;
use App\Models\EnqueteResposta;
use App\Models\Member;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EnqueteService
{
    public function __construct(
        private WhatsAppService $whatsappService
    ) {}

    /**
     * Envia enquete (texto com opções) para membros e/ou departamentos.
     * Grupos = Departamentos (membros vinculados ao departamento).
     */
    public function enviarEnquete(Enquete $enquete, ?array $memberIds = null, ?array $departmentIds = null): array
    {
        $members = collect();

        if (!empty($memberIds)) {
            $members = $members->merge(
                Member::whereIn('id', $memberIds)->whereNotNull('phone')->get()
            );
        }
        if (!empty($departmentIds)) {
            $departments = Department::whereIn('id', $departmentIds)
                ->with(['members' => function ($q) {
                    $q->whereNotNull('members.phone')->where('members.phone', '!=', '');
                }])
                ->get();
            foreach ($departments as $d) {
                $members = $members->merge($d->members);
            }
        }
        if ($members->isEmpty()) {
            $members = Member::whereNotNull('phone')->where('phone', '!=', '')->get();
        }

        $members = $members->unique('id');
        $opcoes = $enquete->opcoes ?? [];
        $enviadas = 0;
        $erros = 0;
        $primeiroErro = null;

        foreach ($members as $member) {
            $phone = $member->phone;
            if (empty($phone)) {
                continue;
            }
            $r = $this->whatsappService->enviarEnquetePoll(
                $phone,
                (string) $enquete->titulo,
                (string) ($enquete->descricao ?? ''),
                $opcoes,
                $enquete->id
            );
            if ($r['success'] ?? false) {
                $enviadas++;
                EnqueteEnvio::create([
                    'enquete_id' => $enquete->id,
                    'member_id' => $member->id,
                    'telefone' => WhatsAppService::normalizarNumero($phone),
                    'status' => 'enviado',
                    'enviado_em' => now(),
                ]);
            } else {
                $erros++;
                if ($primeiroErro === null) {
                    $primeiroErro = (string) ($r['error'] ?? 'Falha ao enviar enquete');
                }
            }
        }

        return [
            'enviadas' => $enviadas,
            'erros' => $erros,
            'total' => $members->count(),
            'primeiro_erro' => $primeiroErro,
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function processarWebhookPayload(array $payload): bool
    {
        $processou = false;

        foreach ($this->normalizarItensMensagem($payload) as $item) {
            if ($this->processarItemMensagem($payload, $item)) {
                $processou = true;
            }
        }

        return $processou;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<int, array<string, mixed>>
     */
    private function normalizarItensMensagem(array $payload): array
    {
        $data = $payload['data'] ?? null;

        if (is_array($data) && array_is_list($data)) {
            return array_values(array_filter($data, 'is_array'));
        }

        if (is_array($data)) {
            return [$data];
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $item
     */
    private function processarItemMensagem(array $payload, array $item): bool
    {
        $evento = Str::lower((string) ($payload['event'] ?? ''));
        $evento = str_replace(['.', '-'], '_', $evento);
        // Evolution GO: Message | Evolution API (legado): messages_upsert
        $eventosAceitos = ['message', 'messages_upsert', 'messagesupsert', 'poll'];
        if ($evento !== '' && !in_array($evento, $eventosAceitos, true)) {
            Log::info('Enquete webhook: evento ignorado.', ['event' => $payload['event'] ?? null]);

            return false;
        }

        // Evolution GO usa data.Info / data.Message; Evolution API usa key / message.
        $info = is_array($item['Info'] ?? null) ? $item['Info'] : [];
        $key = is_array($item['key'] ?? null) ? $item['key'] : [];

        $fromMe = (bool) ($info['IsFromMe'] ?? $key['fromMe'] ?? false);
        if ($fromMe) {
            return false;
        }

        $chat = (string) ($info['Chat'] ?? $key['remoteJid'] ?? '');
        if ($this->ehConversaColetiva($chat) || (bool) ($info['IsGroup'] ?? false)) {
            return false;
        }

        $telefone = $this->extrairTelefone($key, $payload, $item, $info);
        if ($telefone === '') {
            Log::info('Enquete webhook: telefone não identificado.', [
                'remoteJid' => $key['remoteJid'] ?? null,
                'remoteJidAlt' => $key['remoteJidAlt'] ?? null,
                'sender' => $info['Sender'] ?? $payload['sender'] ?? null,
                'chat' => $info['Chat'] ?? null,
            ]);

            return false;
        }

        $variantesTelefone = WhatsAppService::variantesNumero($telefone);

        $messageRaw = $item['Message'] ?? $item['message'] ?? [];
        $message = $this->desembrulharMensagem(is_array($messageRaw) ? $messageRaw : []);

        if ($this->ehMensagemIgnorada($message)) {
            return false;
        }

        [$buttonId, $textoResposta, $interativa] = $this->extrairRespostaBotao($message);

        if ($buttonId === null && $textoResposta === '') {
            Log::info('Enquete webhook: sem resposta de botão/texto reconhecível.', [
                'telefone' => $telefone,
                'message_keys' => array_keys($message),
            ]);

            return false;
        }

        $envio = EnqueteEnvio::query()
            ->where('status', 'enviado')
            ->whereIn('telefone', $variantesTelefone)
            ->whereHas('enquete', fn ($q) => $this->aplicarEscopoEnqueteVigente($q))
            ->latest('enviado_em')
            ->first();

        if (!$envio) {
            Log::info('Enquete webhook: nenhum envio pendente.', [
                'telefone' => $telefone,
                'variantes' => $variantesTelefone,
                'button_id' => $buttonId,
                'texto' => $textoResposta,
            ]);

            return false;
        }

        $telefoneCanonico = WhatsAppService::normalizarNumero($envio->telefone);

        $enquete = Enquete::find($envio->enquete_id);
        if (!$enquete) {
            return false;
        }

        $opcoes = $enquete->opcoes ?? [];
        $respostaTexto = $this->resolverTextoOpcao($opcoes, $buttonId, $textoResposta);
        if ($respostaTexto === null) {
            Log::info('Enquete webhook: opção não identificada.', [
                'telefone' => $telefoneCanonico,
                'button_id' => $buttonId,
                'texto' => $textoResposta,
                'interativa' => $interativa,
                'opcoes' => $opcoes,
            ]);

            // Mensagem comum (texto/mídia) não é tentativa de resposta: não devolve aviso.
            if (!$interativa) {
                return false;
            }

            $this->whatsappService->enviarMensagem(
                $telefoneCanonico,
                $this->mensagemRespostaIncorreta($opcoes)
            );

            return true;
        }

        $respostaExistente = $enquete->respostas()
            ->whereIn('telefone', $variantesTelefone)
            ->latest('respondido_em')
            ->first();

        if ($respostaExistente && $respostaExistente->respondido_em >= $envio->enviado_em) {
            Log::info('Enquete webhook: resposta duplicada ignorada.', [
                'enquete_id' => $enquete->id,
                'telefone' => $telefoneCanonico,
                'envio_id' => $envio->id,
            ]);

            return false;
        }

        $memberId = $envio->member_id;
        if (!$memberId) {
            $memberId = Member::query()
                ->whereNotNull('phone')
                ->get(['id', 'phone'])
                ->first(fn (Member $m) => WhatsAppService::numerosEquivalentes((string) $m->phone, $telefoneCanonico))
                ?->id;
        }

        if ($respostaExistente) {
            $respostaExistente->update([
                'member_id' => $memberId,
                'telefone' => $telefoneCanonico,
                'resposta' => $respostaTexto,
                'respondido_em' => now(),
            ]);
        } else {
            EnqueteResposta::create([
                'enquete_id' => $enquete->id,
                'member_id' => $memberId,
                'telefone' => $telefoneCanonico,
                'resposta' => $respostaTexto,
                'respondido_em' => now(),
            ]);
        }

        EnqueteEnvio::where('enquete_id', $enquete->id)
            ->whereIn('telefone', $variantesTelefone)
            ->where('status', 'enviado')
            ->update(['status' => 'respondido']);

        $this->whatsappService->enviarMensagem(
            $telefoneCanonico,
            "Obrigado pela sua resposta! Registramos: {$respostaTexto}"
        );

        Log::info('Enquete webhook: resposta registrada.', [
            'enquete_id' => $enquete->id,
            'telefone' => $telefoneCanonico,
            'resposta' => $respostaTexto,
        ]);

        return true;
    }

    /**
     * @param  array<string, mixed>  $key
     * @param  array<string, mixed>  $payload
     * @param  array<string, mixed>  $item
     * @param  array<string, mixed>  $info
     */
    private function extrairTelefone(array $key, array $payload = [], array $item = [], array $info = []): string
    {
        $candidatos = [
            $info['Sender'] ?? null,
            $info['Chat'] ?? null,
            $info['SenderAlt'] ?? null,
            $key['remoteJidAlt'] ?? null,
            $key['participantAlt'] ?? null,
            $key['participant'] ?? null,
            $key['remoteJid'] ?? null,
            $item['sender'] ?? null,
            $payload['sender'] ?? null,
        ];

        foreach ($candidatos as $candidato) {
            $jid = (string) ($candidato ?? '');
            if ($jid === '' || str_contains($jid, '@lid')) {
                continue;
            }

            // Remove device suffix: 5511...:38@s.whatsapp.net
            $jid = explode('@', $jid)[0] ?? '';
            $jid = explode(':', $jid)[0] ?? '';
            $jid = preg_replace('/[^0-9]/', '', $jid) ?? '';

            if ($jid !== '') {
                return WhatsAppService::normalizarNumero($jid);
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $message
     * @return array<string, mixed>
     */
    private function desembrulharMensagem(array $message): array
    {
        $wrappers = [
            'ephemeralMessage',
            'viewOnceMessage',
            'viewOnceMessageV2',
            'viewOnceMessageV2Extension',
            'documentWithCaptionMessage',
            'editedMessage',
        ];

        foreach ($wrappers as $wrapper) {
            $inner = $message[$wrapper]['message'] ?? null;
            if (is_array($inner)) {
                return $this->desembrulharMensagem($inner);
            }
        }

        return $message;
    }

    /**
     * Terceiro elemento indica interação direta com a enquete (botão, lista ou voto),
     * único caso em que o sistema avisa sobre resposta inválida.
     *
     * @param  array<string, mixed>  $message
     * @return array{0: ?string, 1: string, 2: bool}
     */
    private function extrairRespostaBotao(array $message): array
    {
        $buttons = $message['buttonsResponseMessage'] ?? null;
        if (is_array($buttons)) {
            return [
                isset($buttons['selectedButtonId']) ? (string) $buttons['selectedButtonId'] : null,
                trim((string) ($buttons['selectedDisplayText'] ?? '')),
                true,
            ];
        }

        $template = $message['templateButtonReplyMessage'] ?? null;
        if (is_array($template)) {
            return [
                isset($template['selectedId']) ? (string) $template['selectedId'] : null,
                trim((string) ($template['selectedDisplayText'] ?? '')),
                true,
            ];
        }

        $list = $message['listResponseMessage'] ?? null;
        if (is_array($list)) {
            $selectedId = $list['singleSelectReply']['selectedRowId'] ?? null;

            return [
                $selectedId !== null ? (string) $selectedId : null,
                trim((string) ($list['title'] ?? $list['description'] ?? '')),
                true,
            ];
        }

        $interactive = $message['interactiveResponseMessage'] ?? null;
        if (is_array($interactive)) {
            $paramsJson = $interactive['nativeFlowResponseMessage']['paramsJson'] ?? null;
            if (is_string($paramsJson) && $paramsJson !== '') {
                $params = json_decode($paramsJson, true);
                if (is_array($params)) {
                    $id = $params['id'] ?? $params['selectedId'] ?? $params['button_id'] ?? null;
                    $texto = $params['display_text']
                        ?? $params['selectedDisplayText']
                        ?? $params['title']
                        ?? ($interactive['body']['text'] ?? '');

                    return [
                        $id !== null ? (string) $id : null,
                        trim((string) $texto),
                        true,
                    ];
                }
            }

            $bodyText = trim((string) ($interactive['body']['text'] ?? ''));
            if ($bodyText !== '') {
                return [null, $bodyText, true];
            }
        }

        // Voto de enquete nativa (Evolution GO / WhatsApp poll)
        $pollUpdate = $message['pollUpdateMessage'] ?? null;
        if (is_array($pollUpdate)) {
            $voteName = null;
            $selectedOptions = $pollUpdate['vote']['selectedOptions'] ?? $pollUpdate['selectedOptions'] ?? null;
            if (is_array($selectedOptions)) {
                foreach ($selectedOptions as $opt) {
                    if (is_array($opt) && !empty($opt['optionName'])) {
                        $voteName = $opt['optionName'];
                        break;
                    }
                    if (is_string($opt) && $opt !== '') {
                        $voteName = $opt;
                        break;
                    }
                }
            }
            if ($voteName === null) {
                $voteName = $pollUpdate['name'] ?? null;
            }

            return [null, trim((string) ($voteName ?? '')), true];
        }

        $texto = trim((string) (
            $message['conversation']
            ?? ($message['extendedTextMessage']['text'] ?? '')
        ));

        return [null, $texto, false];
    }

    /**
     * Enquete é respondida somente na conversa individual.
     */
    private function ehConversaColetiva(string $jid): bool
    {
        foreach (['@g.us', '@broadcast', '@newsletter'] as $sufixo) {
            if (str_contains($jid, $sufixo)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $message
     */
    private function ehMensagemIgnorada(array $message): bool
    {
        $tipos = [
            'reactionMessage',
            'protocolMessage',
            'senderKeyDistributionMessage',
            'pollCreationMessage',
            'pollCreationMessageV2',
            'pollCreationMessageV3',
        ];

        foreach ($tipos as $tipo) {
            if (isset($message[$tipo])) {
                Log::info('Enquete webhook: tipo de mensagem ignorado.', ['tipo' => $tipo]);

                return true;
            }
        }

        return false;
    }

    /**
     * Só enquetes ativas e dentro do período configurado (inicio_em/fim_em) aceitam resposta.
     */
    private function aplicarEscopoEnqueteVigente(Builder $query): void
    {
        $query->where('ativa', true)
            ->where(fn ($q) => $q->whereNull('inicio_em')->orWhere('inicio_em', '<=', now()))
            ->where(fn ($q) => $q->whereNull('fim_em')->orWhere('fim_em', '>=', now()));
    }

    /**
     * @param  array<int, mixed>  $opcoes
     */
    private function resolverTextoOpcao(array $opcoes, ?string $buttonId, string $textoResposta): ?string
    {
        if ($buttonId !== null && $buttonId !== '') {
            $index = ((int) $buttonId) - 1;
            if (isset($opcoes[$index])) {
                return is_string($opcoes[$index])
                    ? $opcoes[$index]
                    : (string) ($opcoes[$index]['name'] ?? $opcoes[$index]['label'] ?? $opcoes[$index]['text'] ?? '');
            }

            // Alguns clientes devolvem o próprio texto/id da opção.
            foreach ($opcoes as $opcao) {
                $label = is_string($opcao)
                    ? $opcao
                    : (string) ($opcao['name'] ?? $opcao['label'] ?? $opcao['text'] ?? '');

                if ($this->textosEquivalentes($label, $buttonId)) {
                    return $label;
                }
            }
        }

        if ($textoResposta === '') {
            return null;
        }

        foreach ($opcoes as $opcao) {
            $label = is_string($opcao)
                ? $opcao
                : (string) ($opcao['name'] ?? $opcao['label'] ?? $opcao['text'] ?? '');

            if ($this->textosEquivalentes($label, $textoResposta)) {
                return $label;
            }
        }

        return null;
    }

    private function textosEquivalentes(string $a, string $b): bool
    {
        $normalizar = static function (string $t): string {
            $t = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $t) ?? ''));
            $t = preg_replace('/[\p{Extended_Pictographic}\p{Emoji_Modifier}\p{Emoji_Modifier_Base}]/u', '', $t) ?? $t;

            return trim(preg_replace('/\s+/u', ' ', $t) ?? '');
        };

        return $normalizar($a) === $normalizar($b);
    }

    /**
     * @param  array<int, mixed>  $opcoes
     */
    private function mensagemRespostaIncorreta(array $opcoes): string
    {
        $labels = [];
        foreach ($opcoes as $opcao) {
            $label = is_string($opcao)
                ? $opcao
                : (string) ($opcao['name'] ?? $opcao['label'] ?? $opcao['text'] ?? '');
            $label = trim($label);
            if ($label !== '') {
                $labels[] = $label;
            }
        }

        // WhatsApp envia no máximo 3 botões reply.
        $labels = array_slice($labels, 0, 3);
        $qtd = count($labels);

        if ($qtd === 0) {
            return 'Resposta incorreta. Clique em uma das respostas disponíveis.';
        }

        $lista = implode(' ou ', $labels);
        $quantificador = $qtd === 1 ? 'resposta disponível' : "{$qtd} respostas disponíveis";

        return "Resposta incorreta. Clique em uma das {$quantificador}: {$lista}.";
    }
}
