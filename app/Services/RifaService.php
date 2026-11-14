<?php

namespace App\Services;

use App\Models\Rifa;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RifaService
{
    public function criarComNumerosECartelas(array $dados): Rifa
    {
        return DB::transaction(function () use ($dados) {
            $rifa = Rifa::create($dados);

            $this->gerarNumeros($rifa, 1, (int) $rifa->quantidade_numeros);
            if ($rifa->department_id && $rifa->status === 'ativa') $this->distribuirCartelas($rifa);
            return $rifa;
        });
    }

    public function atualizar(Rifa $rifa, array $dados): void
    {
        DB::transaction(function () use ($rifa, $dados) {
            $locked = Rifa::whereKey($rifa->id)->lockForUpdate()->firstOrFail();
            if (!empty($dados['department_id']) && (string) $dados['department_id'] !== (string) $locked->department_id
                && !\App\Models\Department::active()->whereKey($dados['department_id'])->exists()) {
                throw ValidationException::withMessages(['department_id' => 'Selecione um departamento ativo.']);
            }
            if (array_key_exists('department_id', $dados) && (string) $dados['department_id'] !== (string) $locked->department_id
                && $locked->cartelas()->whereNotNull('vendedor_id')->exists()) {
                throw ValidationException::withMessages(['department_id' => 'Esta rifa já tem cartelas distribuídas. Preserve o departamento para manter os vendedores responsáveis.']);
            }
            $locked->update($dados);
        });
    }

    public function adicionarNumeros(Rifa $rifa, int $quantidade): void
    {
        DB::transaction(function () use ($rifa, $quantidade) {
            $locked = Rifa::whereKey($rifa->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'ativa') {
                throw ValidationException::withMessages(['quantidade' => 'Somente rifas ativas podem receber novos números.']);
            }
            // Numeric max preserves the sequence even after it crosses 9999.
            $ultimo = (int) $locked->numeros()->max(DB::raw('CAST(numero AS UNSIGNED)'));
            if ($quantidade < 1 || $ultimo + $quantidade > 100000) {
                throw ValidationException::withMessages(['quantidade' => 'Informe uma quantidade válida, respeitando o limite de 100.000 números por rifa.']);
            }
            $this->gerarNumeros($locked, $ultimo + 1, $quantidade);
            $locked->update(['quantidade_numeros' => $locked->numeros()->count()]);
            if ($locked->department_id) $this->distribuirCartelas($locked);
        });
    }

    public function distribuirCartelas(Rifa $rifa): int
    {
        return DB::transaction(function () use ($rifa) {
            $locked = Rifa::whereKey($rifa->id)->lockForUpdate()->firstOrFail();
            if (!$locked->department_id || $locked->status !== 'ativa') {
                throw ValidationException::withMessages(['department_id' => 'Selecione um departamento em uma rifa ativa para distribuir as cartelas.']);
            }
            $vendedores = $locked->membrosVendedores()->get(['members.id', 'members.name']);
            if ($vendedores->isEmpty()) {
                throw ValidationException::withMessages(['department_id' => 'O departamento escolhido não possui membros ativos para receber cartelas.']);
            }
            $cargas = $locked->cartelas()->whereNotNull('vendedor_id')->select('vendedor_id', DB::raw('COUNT(*) as total'))->groupBy('vendedor_id')->pluck('total', 'vendedor_id')->all();
            $cartelas = $locked->cartelas()->whereNull('vendedor_id')
                ->whereDoesntHave('numeros', fn ($q) => $q->where('status', '!=', 'disponivel'))
                ->orderBy('id')->lockForUpdate()->get();
            foreach ($cartelas as $cartela) {
                $vendedor = $vendedores->sortBy(fn ($membro) => (int) ($cargas[$membro->id] ?? 0))->first();
                $cartela->update(['vendedor_id' => $vendedor->id]);
                $cargas[$vendedor->id] = ($cargas[$vendedor->id] ?? 0) + 1;
            }
            return $cartelas->count();
        });
    }

    private function gerarNumeros(Rifa $rifa, int $inicio, int $quantidade): void
    {
        $padding = max(4, strlen((string) ($inicio + $quantidade - 1)));
        $indiceCartela = $rifa->cartelas()->count();
        $ultimoId = (int) $rifa->numeros()->max('id');
        // Each batch is one new card; existing cards, even partial ones, stay unchanged.
        for ($offset = 0; $offset < $quantidade; $offset += $rifa->numeros_por_cartela) {
            $numeros = [];
            $fim = min($inicio + $offset + $rifa->numeros_por_cartela, $inicio + $quantidade);
            for ($i = $inicio + $offset; $i < $fim; $i++) {
                $numeros[] = ['rifa_id' => $rifa->id, 'numero' => str_pad((string) $i, $padding, '0', STR_PAD_LEFT),
                    'status' => 'disponivel', 'created_at' => now(), 'updated_at' => now()];
            }
            // Keep insert sizes bounded for both MySQL and SQLite.
            foreach (array_chunk($numeros, 100) as $rows) DB::table('numeros_rifa')->insert($rows);
            $ids = $rifa->numeros()->where('id', '>', $ultimoId)->orderBy('id')->pluck('id');
            $ultimoId = (int) $ids->last();
            $cartela = $rifa->cartelas()->create(['identificador' => 'Cartela '.(++$indiceCartela)]);
            foreach ($ids->chunk(100) as $chunk) $cartela->numeros()->attach($chunk);
        }
    }
}
