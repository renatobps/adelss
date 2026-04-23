<?php

namespace App\Http\Controllers\Rifas;

use App\Http\Controllers\Controller;
use App\Models\Member;
use App\Models\Rifa;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class RelatorioController extends Controller
{
    public function index(Request $request): View
    {
        $rifaId = $request->input('rifa_id');
        $vendedorId = $request->input('vendedor_id');
        $inicio = $request->input('inicio');
        $fim = $request->input('fim');

        $rifas = Rifa::query()->orderBy('nome')->get(['id', 'nome']);
        $vendedores = Member::query()->orderBy('name')->get(['id', 'name']);
        $rifaSelecionada = $rifaId ? Rifa::find($rifaId) : null;

        $numerosQuery = DB::table('numeros_rifa')
            ->join('rifas', 'rifas.id', '=', 'numeros_rifa.rifa_id')
            ->leftJoin('members', 'members.id', '=', 'numeros_rifa.vendedor_id')
            ->whereIn('numeros_rifa.status', ['reservado', 'vendido']);

        if ($rifaId) {
            $numerosQuery->where('numeros_rifa.rifa_id', $rifaId);
        }
        if ($vendedorId) {
            $numerosQuery->where('numeros_rifa.vendedor_id', $vendedorId);
        }
        if ($inicio) {
            $numerosQuery->whereDate('numeros_rifa.data_venda', '>=', $inicio);
        }
        if ($fim) {
            $numerosQuery->whereDate('numeros_rifa.data_venda', '<=', $fim);
        }

        $baseRows = $numerosQuery->select(
            'numeros_rifa.*',
            'rifas.nome as rifa_nome',
            'rifas.valor_numero',
            'members.name as vendedor_nome'
        )->get();

        $total = $baseRows->count();
        $vendidos = $baseRows->where('status', 'vendido')->count();
        $reservados = $baseRows->where('status', 'reservado')->count();
        $arrecadado = $baseRows->where('status', 'vendido')->sum('valor_numero');

        $disponiveis = 0;
        if ($rifaSelecionada) {
            $disponiveis = $rifaSelecionada->numeros()->where('status', 'disponivel')->count();
        }

        $ranking = $baseRows
            ->whereNotNull('vendedor_id')
            ->groupBy('vendedor_id')
            ->map(function ($itens) {
                return [
                    'vendedor' => $itens->first()->vendedor_nome ?? 'Sem vendedor',
                    'quantidade' => $itens->where('status', 'vendido')->count(),
                    'valor' => $itens->where('status', 'vendido')->sum('valor_numero'),
                ];
            })
            ->sortByDesc('quantidade')
            ->values();

        $periodo = $this->gerarSeriePeriodo($baseRows, $inicio, $fim);

        return view('rifas.relatorios.dashboard', [
            'rifas' => $rifas,
            'vendedores' => $vendedores,
            'rifaSelecionada' => $rifaSelecionada,
            'resumo' => compact('total', 'vendidos', 'reservados', 'disponiveis', 'arrecadado'),
            'ranking' => $ranking,
            'periodo' => $periodo,
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        $dados = $this->obterDadosExportacao($request);

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="relatorio-rifas.csv"',
        ];

        return response()->stream(function () use ($dados) {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['Rifa', 'Numero', 'Status', 'Comprador', 'Telefone', 'Vendedor', 'Data venda', 'Valor']);
            foreach ($dados as $row) {
                fputcsv($output, [
                    $row->rifa_nome,
                    $row->numero,
                    $row->status,
                    $row->comprador_nome,
                    $row->comprador_telefone,
                    $row->vendedor_nome,
                    $row->data_venda,
                    number_format((float) $row->valor_numero, 2, ',', '.'),
                ]);
            }
            fclose($output);
        }, 200, $headers);
    }

    public function exportPdf(Request $request)
    {
        $dados = $this->obterDadosExportacao($request);

        $pdf = Pdf::loadView('rifas.relatorios.pdf', [
            'dados' => $dados,
            'geradoEm' => now()->format('d/m/Y H:i'),
        ]);

        return $pdf->download('relatorio-rifas.pdf');
    }

    private function obterDadosExportacao(Request $request)
    {
        $query = DB::table('numeros_rifa')
            ->join('rifas', 'rifas.id', '=', 'numeros_rifa.rifa_id')
            ->leftJoin('members', 'members.id', '=', 'numeros_rifa.vendedor_id')
            ->select(
                'rifas.nome as rifa_nome',
                'rifas.valor_numero',
                'numeros_rifa.numero',
                'numeros_rifa.status',
                'numeros_rifa.comprador_nome',
                'numeros_rifa.comprador_telefone',
                'members.name as vendedor_nome',
                DB::raw("DATE_FORMAT(numeros_rifa.data_venda, '%d/%m/%Y %H:%i') as data_venda")
            )
            ->whereIn('numeros_rifa.status', ['reservado', 'vendido']);

        if ($request->filled('rifa_id')) {
            $query->where('numeros_rifa.rifa_id', $request->integer('rifa_id'));
        }

        if ($request->filled('vendedor_id')) {
            $query->where('numeros_rifa.vendedor_id', $request->integer('vendedor_id'));
        }

        if ($request->filled('inicio')) {
            $query->whereDate('numeros_rifa.data_venda', '>=', $request->input('inicio'));
        }

        if ($request->filled('fim')) {
            $query->whereDate('numeros_rifa.data_venda', '<=', $request->input('fim'));
        }

        return $query->orderBy('rifa_nome')->orderBy('numero')->get();
    }

    private function gerarSeriePeriodo($rows, ?string $inicio, ?string $fim): array
    {
        $inicioData = $inicio ? Carbon::parse($inicio) : now()->subDays(6)->startOfDay();
        $fimData = $fim ? Carbon::parse($fim) : now()->endOfDay();

        $cursor = $inicioData->copy()->startOfDay();
        $labels = [];
        $quantidades = [];
        $valores = [];

        while ($cursor->lte($fimData)) {
            $chave = $cursor->format('Y-m-d');
            $labels[] = $cursor->format('d/m');

            $diaRows = $rows->filter(function ($item) use ($chave) {
                if (!$item->data_venda) {
                    return false;
                }
                return Carbon::parse($item->data_venda)->format('Y-m-d') === $chave;
            });

            $quantidades[] = $diaRows->where('status', 'vendido')->count();
            $valores[] = (float) $diaRows->where('status', 'vendido')->sum('valor_numero');

            $cursor->addDay();
        }

        return compact('labels', 'quantidades', 'valores');
    }
}
