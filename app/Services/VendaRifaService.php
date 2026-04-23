<?php

namespace App\Services;

use App\Models\NumeroRifa;
use App\Models\Rifa;
use App\Models\RifaVenda;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VendaRifaService
{
    public function registrarVendaRapida(Rifa $rifa, array $dados): RifaVenda
    {
        return DB::transaction(function () use ($rifa, $dados) {
            $numeroIds = $dados['numero_ids'];
            $statusFinal = $dados['status'] ?? 'vendido';

            $numeros = NumeroRifa::query()
                ->where('rifa_id', $rifa->id)
                ->whereIn('id', $numeroIds)
                ->lockForUpdate()
                ->get();

            if ($numeros->count() !== count($numeroIds)) {
                throw ValidationException::withMessages([
                    'numero_ids' => 'Um ou mais números selecionados não pertencem à rifa.',
                ]);
            }

            $indisponiveis = $numeros->where('status', 'vendido');
            if ($indisponiveis->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'numero_ids' => 'Existem números já vendidos na seleção.',
                ]);
            }

            $venda = RifaVenda::create([
                'rifa_id' => $rifa->id,
                'vendedor_id' => $dados['vendedor_id'],
                'comprador_nome' => $dados['comprador_nome'],
                'comprador_telefone' => $dados['comprador_telefone'] ?? null,
                'status' => $statusFinal,
                'valor_total' => $rifa->valor_numero * count($numeroIds),
                'data_venda' => now(),
            ]);

            NumeroRifa::query()
                ->whereIn('id', $numeroIds)
                ->update([
                    'venda_id' => $venda->id,
                    'status' => $statusFinal,
                    'comprador_nome' => $dados['comprador_nome'],
                    'comprador_telefone' => $dados['comprador_telefone'] ?? null,
                    'vendedor_id' => $dados['vendedor_id'],
                    'data_venda' => now(),
                    'updated_at' => now(),
                ]);

            return $venda;
        });
    }

    public function cancelarNumero(NumeroRifa $numero): void
    {
        DB::transaction(function () use ($numero) {
            $numero->refresh();
            if ($numero->status === 'disponivel') {
                return;
            }

            $vendaId = $numero->venda_id;

            $numero->update([
                'venda_id' => null,
                'status' => 'disponivel',
                'comprador_nome' => null,
                'comprador_telefone' => null,
                'vendedor_id' => null,
                'data_venda' => null,
            ]);

            if ($vendaId) {
                $aindaPossuiNumeros = NumeroRifa::where('venda_id', $vendaId)->exists();
                if (!$aindaPossuiNumeros) {
                    RifaVenda::where('id', $vendaId)->update(['status' => 'cancelada']);
                }
            }
        });
    }
}
