<?php

namespace App\Http\Controllers\Rifas;

use App\Http\Controllers\Controller;
use App\Http\Requests\Rifas\StoreRifaRequest;
use App\Http\Requests\Rifas\UpdateRifaRequest;
use App\Models\Member;
use App\Models\NumeroRifa;
use App\Models\Rifa;
use App\Models\SorteioRifa;
use App\Services\RifaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RifaController extends Controller
{
    public function __construct(
        private RifaService $rifaService
    ) {}

    public function index(Request $request): View
    {
        $query = Rifa::query()->withCount('numeros');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = (string) $request->string('search');
            $query->where('nome', 'like', "%{$search}%");
        }

        $rifas = $query->latest()->paginate(10)->withQueryString();

        return view('rifas.index', compact('rifas'));
    }

    public function create(): View
    {
        $this->authorize('create', Rifa::class);

        return view('rifas.create');
    }

    public function store(StoreRifaRequest $request): RedirectResponse
    {
        $rifa = $this->rifaService->criarComNumerosECartelas($request->validated());

        return redirect()
            ->route('rifas.show', $rifa)
            ->with('success', 'Rifa criada com números e cartelas automaticamente.');
    }

    public function show(Rifa $rifa, Request $request): View
    {
        $this->authorize('view', $rifa);

        $numerosQuery = NumeroRifa::query()
            ->with('vendedor')
            ->where('rifa_id', $rifa->id)
            ->orderBy('numero');

        if ($request->filled('status')) {
            $numerosQuery->where('status', (string) $request->string('status'));
        }

        if ($request->filled('vendedor_id')) {
            $numerosQuery->where('vendedor_id', $request->integer('vendedor_id'));
        }

        if ($request->filled('comprador')) {
            $comprador = (string) $request->string('comprador');
            $numerosQuery->where('comprador_nome', 'like', "%{$comprador}%");
        }

        $numeros = $numerosQuery->paginate(40)->withQueryString();

        $resumo = [
            'total' => $rifa->numeros()->count(),
            'vendidos' => $rifa->numeros()->where('status', 'vendido')->count(),
            'reservados' => $rifa->numeros()->where('status', 'reservado')->count(),
            'disponiveis' => $rifa->numeros()->where('status', 'disponivel')->count(),
        ];
        $resumo['arrecadado'] = ($resumo['vendidos'] * (float) $rifa->valor_numero);

        $vendedores = Member::query()->orderBy('name')->get(['id', 'name']);

        return view('rifas.show', compact('rifa', 'numeros', 'resumo', 'vendedores'));
    }

    public function edit(Rifa $rifa): View
    {
        $this->authorize('update', $rifa);

        return view('rifas.edit', compact('rifa'));
    }

    public function update(UpdateRifaRequest $request, Rifa $rifa): RedirectResponse
    {
        $rifa->update($request->validated());

        return redirect()->route('rifas.show', $rifa)->with('success', 'Rifa atualizada com sucesso.');
    }

    public function destroy(Rifa $rifa): RedirectResponse
    {
        $this->authorize('delete', $rifa);

        $rifa->update(['status' => 'cancelada']);

        return redirect()->route('rifas.index')->with('success', 'Rifa cancelada com sucesso.');
    }

    public function updateStatus(Request $request, Rifa $rifa): RedirectResponse
    {
        $this->authorize('update', $rifa);

        $dados = $request->validate([
            'status' => ['required', 'in:ativa,finalizada,cancelada'],
        ]);

        $rifa->update(['status' => $dados['status']]);

        return back()->with('success', 'Status da rifa atualizado.');
    }

    public function sortear(Request $request, Rifa $rifa): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $rifa);

        $numero = $rifa->numeros()
            ->with('vendedor:id,name')
            ->where('status', 'vendido')
            ->inRandomOrder()
            ->first();

        if (!$numero) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Não há números vendidos para realizar sorteio.',
                ], 422);
            }

            return back()->with('error', 'Não há números vendidos para realizar sorteio.');
        }

        SorteioRifa::create([
            'rifa_id' => $rifa->id,
            'numero_rifa_id' => $numero->id,
            'numero' => $numero->numero,
            'comprador_nome' => $numero->comprador_nome,
            'vendedor_id' => $numero->vendedor_id,
            'vendedor_nome' => $numero->vendedor?->name,
            'sorteado_por_id' => auth()->id(),
        ]);

        $mensagem = "Número sorteado: {$numero->numero} - Comprador: {$numero->comprador_nome}.";

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $mensagem,
                'data' => [
                    'numero' => $numero->numero,
                    'comprador' => $numero->comprador_nome,
                    'vendedor' => $numero->vendedor?->name ?: 'Não informado',
                ],
            ]);
        }

        return back()->with(
            'success',
            $mensagem
        );
    }

    public function sorteios(Rifa $rifa): View
    {
        $this->authorize('view', $rifa);

        $sorteios = $rifa->sorteios()
            ->with(['vendedor:id,name', 'sorteadoPor:id,name'])
            ->latest()
            ->paginate(20);

        return view('rifas.sorteios.index', compact('rifa', 'sorteios'));
    }

    public function destroySorteio(Rifa $rifa, SorteioRifa $sorteio): RedirectResponse
    {
        $this->authorize('update', $rifa);

        if ((int) $sorteio->rifa_id !== (int) $rifa->id) {
            abort(404);
        }

        $sorteio->delete();

        return back()->with('success', 'Sorteio excluído com sucesso.');
    }
}
