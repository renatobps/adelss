<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Enquete;
use App\Models\EnqueteEnvio;
use App\Models\Member;
use Illuminate\Support\Facades\Log;

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
        $mensagem = $this->montarMensagemEnquete($enquete);
        $opcoes = $enquete->opcoes ?? [];
        $enviadas = 0;
        $erros = 0;

        foreach ($members as $member) {
            $phone = $member->phone;
            if (empty($phone)) {
                continue;
            }
            $r = $this->whatsappService->enviarEnquetePoll(
                $phone,
                $mensagem,
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
            }
        }

        return ['enviadas' => $enviadas, 'erros' => $erros, 'total' => $members->count()];
    }

    private function montarMensagemEnquete(Enquete $enquete): string
    {
        $linhas = [];
        if ($enquete->titulo) {
            $linhas[] = "📊 *{$enquete->titulo}*";
        }
        if ($enquete->descricao) {
            $linhas[] = "\n" . $enquete->descricao;
        }
        $linhas[] = "\nPor favor, responda com uma das opções:";
        foreach ($enquete->opcoes ?? [] as $i => $op) {
            $linhas[] = ($i + 1) . ". " . $op;
        }
        return implode("\n", $linhas);
    }
}
