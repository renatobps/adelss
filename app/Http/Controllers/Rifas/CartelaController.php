<?php

namespace App\Http\Controllers\Rifas;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Cartela;
use App\Models\Rifa;
use App\Services\RifaService;
use App\Services\RifaCartelaWhatsappService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CartelaController extends Controller
{
    public function index(Rifa $rifa, Request $request): View
    {
        $this->authorize('view', $rifa);

        $cartelas = $rifa->cartelas()
            ->with(['vendedor', 'numeros' => function ($query) {
                $query->orderBy('numeros_rifa.id');
            }])
            ->when($request->filled('vendedor_id'), fn ($q) => $q->where('vendedor_id', $request->integer('vendedor_id')))
            ->orderBy('id')
            ->paginate(12)->withQueryString();

        $vendedores = $rifa->membrosVendedores()->get(['members.id', 'members.name']);
        $vendedoresDistribuidos = Member::query()->whereIn('id', $rifa->cartelas()->whereNotNull('vendedor_id')->select('vendedor_id'))->orderBy('name')->get(['id', 'name']);
        $numerosParaVenda = $rifa->numeros()
            ->whereIn('status', ['disponivel', 'reservado'])
            ->orderBy('numero')
            ->get(['id', 'numero', 'status']);
        $numerosEditaveis = $rifa->numeros()
            ->whereIn('status', ['reservado', 'vendido'])
            ->orderBy('numero')
            ->get(['id', 'numero', 'status']);

        return view('rifas.cartelas.index', compact(
            'rifa',
            'cartelas',
            'vendedores',
            'numerosParaVenda',
            'numerosEditaveis',
            'vendedoresDistribuidos'
        ));
    }

    public function imprimir(Rifa $rifa): View
    {
        $this->authorize('view', $rifa);

        $cartelas = $rifa->cartelas()
            ->with(['vendedor', 'numeros' => function ($query) {
                $query->orderBy('numeros_rifa.id');
            }])
            ->orderBy('id')
            ->get();

        return view('rifas.cartelas.print', compact('rifa', 'cartelas'));
    }

    public function distribuir(Rifa $rifa, RifaService $service): RedirectResponse
    {
        $this->authorize('update', $rifa);
        $total = $service->distribuirCartelas($rifa);
        return back()->with('success', $total.' cartela(s) distribuída(s). Cartelas já atribuídas, reservadas ou vendidas foram preservadas.');
    }

    public function pdf(Rifa $rifa, Cartela $cartela)
    {
        $this->authorize('view', $rifa);
        abort_unless((int) $cartela->rifa_id === (int) $rifa->id, 404);
        return $this->gerarPdf($rifa, $cartela)->download('rifa-'.$rifa->id.'-cartela-'.$cartela->id.'.pdf');
    }

    public function whatsapp(Rifa $rifa, Cartela $cartela, RifaCartelaWhatsappService $service): RedirectResponse
    {
        $this->authorize('update', $rifa);
        abort_unless((int) $cartela->rifa_id === (int) $rifa->id, 404);
        $cartela->load('vendedor');
        if (!$cartela->vendedor || empty($cartela->vendedor->phone)) {
            return back()->with('error', 'Atribua um vendedor com telefone cadastrado a esta cartela.');
        }
        $result = $service->enviar($rifa, $cartela->vendedor, new \Illuminate\Database\Eloquent\Collection([$cartela]));
        return back()->with($result['success'] ? 'success' : 'error', $result['message']);
    }

    public function destinatarios(Rifa $rifa): \Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $rifa);
        if (!$rifa->pix_chave || !$rifa->pix_recebedor) {
            return response()->json(['message' => 'Cadastre a chave PIX e o nome do recebedor antes do envio em lote.'], 422);
        }
        $destinatarios = $rifa->cartelas()->whereNotNull('vendedor_id')->with('vendedor:id,name,phone')->get(['id', 'rifa_id', 'vendedor_id'])
            ->groupBy('vendedor_id')->map(function ($cartelas) use ($rifa) {
                $vendedor = $cartelas->first()->vendedor;
                return [
                    'nome' => $vendedor?->name ?? 'Membro indisponível', 'cartelas' => $cartelas->count(),
                    'pode_enviar' => (bool) ($vendedor && !empty($vendedor->phone)),
                    'url' => $vendedor ? route('rifas.cartelas.whatsapp-vendedor', [$rifa, $vendedor]) : null,
                ];
            })->values();
        return response()->json(['destinatarios' => $destinatarios])->header('Cache-Control', 'no-store, private');
    }

    public function whatsappVendedor(Rifa $rifa, Member $vendedor, RifaCartelaWhatsappService $service): \Illuminate\Http\JsonResponse
    {
        $this->authorize('update', $rifa);
        if (!$rifa->pix_chave || !$rifa->pix_recebedor) {
            return response()->json(['success' => false, 'message' => 'Cadastre a chave PIX e o nome do recebedor antes do envio em lote.'], 422);
        }
        $cartelas = $rifa->cartelas()->where('vendedor_id', $vendedor->id)->orderBy('id')->get();
        abort_if($cartelas->isEmpty(), 404);
        $result = $service->enviar($rifa, $vendedor, $cartelas);
        return response()->json($result, $result['success'] ? 200 : 422);
    }

    private function gerarPdf(Rifa $rifa, Cartela $cartela)
    {
        $cartela->load(['vendedor', 'numeros' => fn ($q) => $q->orderBy('numeros_rifa.id')]);
        return Pdf::loadView('rifas.cartelas.pdf', compact('rifa', 'cartela'))->setPaper('a4');
    }
}
