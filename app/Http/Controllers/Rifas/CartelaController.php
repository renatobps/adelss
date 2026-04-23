<?php

namespace App\Http\Controllers\Rifas;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Rifa;
use Illuminate\View\View;

class CartelaController extends Controller
{
    public function index(Rifa $rifa): View
    {
        $this->authorize('view', $rifa);

        $cartelas = $rifa->cartelas()
            ->with(['numeros' => function ($query) {
                $query->orderBy('numero');
            }])
            ->orderBy('id')
            ->paginate(12);

        $vendedores = Member::query()->orderBy('name')->get(['id', 'name']);
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
            'numerosEditaveis'
        ));
    }

    public function imprimir(Rifa $rifa): View
    {
        $this->authorize('view', $rifa);

        $cartelas = $rifa->cartelas()
            ->with(['numeros' => function ($query) {
                $query->orderBy('numero');
            }])
            ->orderBy('id')
            ->get();

        return view('rifas.cartelas.print', compact('rifa', 'cartelas'));
    }
}
