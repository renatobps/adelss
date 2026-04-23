<?php

namespace App\Services;

use App\Models\Rifa;
use Illuminate\Support\Facades\DB;

class RifaService
{
    public function criarComNumerosECartelas(array $dados): Rifa
    {
        return DB::transaction(function () use ($dados) {
            $rifa = Rifa::create($dados);

            $padding = max(4, strlen((string) $rifa->quantidade_numeros));
            $numeros = [];
            for ($i = 1; $i <= $rifa->quantidade_numeros; $i++) {
                $numeros[] = [
                    'rifa_id' => $rifa->id,
                    'numero' => str_pad((string) $i, $padding, '0', STR_PAD_LEFT),
                    'status' => 'disponivel',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            DB::table('numeros_rifa')->insert($numeros);

            $numerosIds = DB::table('numeros_rifa')
                ->where('rifa_id', $rifa->id)
                ->orderBy('id')
                ->pluck('id')
                ->all();

            $chunks = array_chunk($numerosIds, $rifa->numeros_por_cartela);

            foreach ($chunks as $index => $chunkIds) {
                $cartela = DB::table('cartelas')->insertGetId([
                    'rifa_id' => $rifa->id,
                    'identificador' => 'Cartela ' . ($index + 1),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $cartelaNumeros = [];
                foreach ($chunkIds as $numeroId) {
                    $cartelaNumeros[] = [
                        'cartela_id' => $cartela,
                        'numero_id' => $numeroId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                DB::table('cartela_numero')->insert($cartelaNumeros);
            }

            return $rifa;
        });
    }
}
