<?php

namespace App\Http\Controllers\Rifas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rifas\AtualizarNumeroRifaRequest;
use App\Http\Requests\Rifas\RegistrarVendaRapidaRequest;
use App\Models\Member;
use App\Models\NumeroRifa;
use App\Models\Rifa;
use App\Services\VendaRifaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VendaRapidaController extends Controller
{
    public function __construct(
        private VendaRifaService $vendaRifaService
    ) {}

    public function create(Rifa $rifa): View
    {
        $this->authorize('sell', $rifa);

        $numerosDisponiveis = $rifa->numeros()
            ->whereIn('status', ['disponivel', 'reservado'])
            ->orderBy('numero')
            ->get(['id', 'numero', 'status']);

        $vendedores = Member::query()->orderBy('name')->get(['id', 'name']);

        return view('rifas.vendas.rapida', compact('rifa', 'numerosDisponiveis', 'vendedores'));
    }

    public function store(RegistrarVendaRapidaRequest $request, Rifa $rifa): RedirectResponse
    {
        $this->vendaRifaService->registrarVendaRapida($rifa, $request->validated());

        $returnTo = $request->input('return_to');
        if (is_string($returnTo) && $returnTo !== '') {
            $appUrl = rtrim((string) config('app.url'), '/');
            if ($appUrl !== '' && str_starts_with($returnTo, $appUrl)) {
                return redirect()->to($returnTo)->with('success', 'Venda registrada com sucesso.');
            }
        }

        return redirect()->route('rifas.show', $rifa)->with('success', 'Venda registrada com sucesso.');
    }

    public function atualizarComprador(
        AtualizarNumeroRifaRequest $request,
        NumeroRifa $numero
    ): RedirectResponse {
        $dados = $request->validated();
        if (empty($dados['vendedor_id'])) {
            $dados['vendedor_id'] = null;
        }

        $numero->update($dados);

        return back()->with('success', 'Dados do comprador atualizados.');
    }

    public function cancelarNumero(NumeroRifa $numero): RedirectResponse
    {
        $this->authorize('sell', $numero->rifa);

        $this->vendaRifaService->cancelarNumero($numero);

        return back()->with('success', 'Venda cancelada e número devolvido para disponível.');
    }

    public function atualizarCompradorEmLote(Request $request, Rifa $rifa): RedirectResponse
    {
        $this->authorize('sell', $rifa);

        $dados = $request->validate([
            'escopo' => ['required', 'in:um,selecionados'],
            'numero_id' => ['required', 'integer', 'exists:numeros_rifa,id'],
            'numero_ids' => ['nullable', 'array'],
            'numero_ids.*' => ['integer', 'exists:numeros_rifa,id'],
            'comprador_nome' => ['required', 'string', 'max:255'],
            'comprador_telefone' => ['nullable', 'string', 'max:30'],
            'vendedor_id' => ['nullable', 'exists:members,id'],
        ]);

        $ids = [$dados['numero_id']];
        if ($dados['escopo'] === 'selecionados') {
            $ids = collect($dados['numero_ids'] ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->unique()
                ->values()
                ->all();
        }

        if (empty($ids)) {
            return back()->with('error', 'Selecione ao menos um número para editar.');
        }

        $atualizados = NumeroRifa::query()
            ->where('rifa_id', $rifa->id)
            ->whereIn('status', ['reservado', 'vendido'])
            ->whereIn('id', $ids)
            ->update([
                'comprador_nome' => $dados['comprador_nome'],
                'comprador_telefone' => $dados['comprador_telefone'] ?? null,
                'vendedor_id' => $dados['vendedor_id'] ?? null,
                'updated_at' => now(),
            ]);

        if ($atualizados === 0) {
            return back()->with('error', 'Nenhum número elegível foi atualizado.');
        }

        return back()->with('success', "Dados atualizados em {$atualizados} número(s).");
    }
}
