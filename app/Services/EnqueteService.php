<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Enquete;
use App\Models\EnqueteEnvio;
use App\Models\EnqueteResposta;
use App\Models\Member;
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
        if ($evento !== '' && !in_array($evento, ['messages.upsert', 'messages_upsert'], true)) {
            return false;
        }

        $key = is_array($item['key'] ?? null) ? $item['key'] : [];
        if (($key['fromMe'] ?? false) === true) {
            return false;
        }

        $telefone = $this->extrairTelefone($key);
        if ($telefone === '') {
            return false;
        }

        $variantesTelefone = WhatsAppService::variantesNumero($telefone);

        $message = is_array($item['message'] ?? null) ? $item['message'] : [];
        [$buttonId, $textoResposta] = $this->extrairRespostaBotao($message);

        if ($buttonId === null && $textoResposta === '') {
            return false;
        }

        $envio = EnqueteEnvio::where('status', 'enviado')
            ->whereIn('telefone', $variantesTelefone)
            ->latest('enviado_em')
            ->first();

        if (!$envio) {
            Log::info('Enquete webhook: nenhum envio pendente.', [
                'telefone' => $telefone,
                'variantes' => $variantesTelefone,
            ]);

            return false;
        }

        $telefoneCanonico = WhatsAppService::normalizarNumero($envio->telefone);

        $enquete = Enquete::find($envio->enquete_id);
        if (!$enquete) {
            return false;
        }

        $respostaTexto = $this->resolverTextoOpcao($enquete->opcoes ?? [], $buttonId, $textoResposta);
        if ($respostaTexto === null) {
            Log::info('Enquete webhook: opção não identificada.', [
                'telefone' => $telefoneCanonico,
                'button_id' => $buttonId,
                'texto' => $textoResposta,
            ]);

            return false;
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
     */
    private function extrairTelefone(array $key): string
    {
        $jid = (string) ($key['remoteJidAlt'] ?? $key['remoteJid'] ?? '');
        $jid = explode('@', $jid)[0] ?? '';
        $jid = preg_replace('/[^0-9]/', '', $jid) ?? '';

        return $jid !== '' ? WhatsAppService::normalizarNumero($jid) : '';
    }

    /**
     * @param  array<string, mixed>  $message
     * @return array{0: ?string, 1: string}
     */
    private function extrairRespostaBotao(array $message): array
    {
        $buttons = $message['buttonsResponseMessage'] ?? null;
        if (is_array($buttons)) {
            return [
                isset($buttons['selectedButtonId']) ? (string) $buttons['selectedButtonId'] : null,
                trim((string) ($buttons['selectedDisplayText'] ?? '')),
            ];
        }

        $template = $message['templateButtonReplyMessage'] ?? null;
        if (is_array($template)) {
            return [
                isset($template['selectedId']) ? (string) $template['selectedId'] : null,
                trim((string) ($template['selectedDisplayText'] ?? '')),
            ];
        }

        $texto = trim((string) (
            $message['conversation']
            ?? ($message['extendedTextMessage']['text'] ?? '')
        ));

        return [null, $texto];
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
}
