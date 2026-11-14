<?php

namespace App\Http\Controllers\Rifas;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Cartela;
use App\Models\Rifa;
use App\Services\RifaService;
use App\Services\WhatsAppService;
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

    public function whatsapp(Rifa $rifa, Cartela $cartela, WhatsAppService $whatsapp): RedirectResponse
    {
        $this->authorize('update', $rifa);
        abort_unless((int) $cartela->rifa_id === (int) $rifa->id, 404);
        $cartela->load('vendedor');
        if (!$cartela->vendedor || empty($cartela->vendedor->phone)) {
            return back()->with('error', 'Atribua um vendedor com telefone cadastrado a esta cartela.');
        }
        if (!$whatsapp->isConfigurado()) return back()->with('error', 'Configure a conexão WhatsApp antes de enviar a cartela.');
        $path = null;
        try {
            $path = tempnam(sys_get_temp_dir(), 'rifa-cartela-');
            if ($path === false) throw new \RuntimeException('Falha ao criar arquivo temporário da cartela.');
            if (file_put_contents($path, $this->gerarPdf($rifa, $cartela)->output()) === false) {
                throw new \RuntimeException('Falha ao gravar cartela temporária.');
            }
            $result = $whatsapp->enviarDocumentoArquivo(
                $cartela->vendedor->phone, $path, 'rifa-'.$rifa->id.'-cartela-'.$cartela->id.'.pdf',
                'Olá, '.$cartela->vendedor->name.'! Segue sua '.$cartela->identificador.' da rifa '.$rifa->nome.'. Valor por número: R$ '.number_format((float) $rifa->valor_numero, 2, ',', '.').'.'
            );
            if (!($result['success'] ?? false)) return back()->with('error', 'O WhatsApp não confirmou o envio da cartela. Confira a conexão e tente novamente.');
            $cartela->update(['whatsapp_enviado_em' => now()]);
            return back()->with('success', 'Cartela enviada ao WhatsApp de '.$cartela->vendedor->name.'.');
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Não foi possível enviar a cartela. Confira a conexão WhatsApp e tente novamente.');
        } finally {
            if (is_string($path) && is_file($path)) unlink($path);
        }
    }

    private function gerarPdf(Rifa $rifa, Cartela $cartela)
    {
        $cartela->load(['vendedor', 'numeros' => fn ($q) => $q->orderBy('numeros_rifa.id')]);
        return Pdf::loadView('rifas.cartelas.pdf', compact('rifa', 'cartela'))->setPaper('a4');
    }
}
