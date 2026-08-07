@php
    use App\Support\PdfText;
    $campaignName = PdfText::stripEmoji($campaign->name);
    $departmentName = $campaign->department ? PdfText::stripEmoji($campaign->department->name) : null;
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Prestação de Contas — {{ $campaignName }}</title>
    <style>
        @page {
            margin: 0;
            size: A4 portrait;
        }
        body {
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            font-size: 11px;
            color: #1f2937;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }
        .page { padding: 26px; }
        .header {
            background-color: #1e3a8a;
            color: #ffffff;
            padding: 16px 18px;
            margin-bottom: 16px;
        }
        .header h1 { margin: 0; font-size: 16px; }
        .header p { margin: 4px 0 0 0; font-size: 10px; opacity: 0.85; }
        h2 {
            font-size: 12px;
            color: #1e3a8a;
            text-transform: uppercase;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
            margin: 18px 0 8px 0;
        }
        table.dados {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        table.dados th, table.dados td {
            border: 1px solid #cbd5e1;
            padding: 5px 7px;
            text-align: left;
        }
        table.dados th {
            background-color: #eef2ff;
            font-size: 10px;
            text-transform: uppercase;
            color: #374151;
        }
        .num { text-align: right; }
        .total-row td { background-color: #f1f5f9; font-weight: bold; }
        .rodape {
            margin-top: 22px;
            font-size: 9px;
            color: #6b7280;
            border-top: 1px solid #cbd5e1;
            padding-top: 6px;
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <table width="100%" style="border-collapse: collapse;">
                <tr>
                    <td>
                        <h1>Prestação de Contas — {{ $campaignName }}</h1>
                        <p>
                            @if($departmentName) Departamento: {{ $departmentName }} • @endif
                            Gerado em {{ now()->format('d/m/Y H:i') }}
                            @if($from || $to)
                                • Período: {{ $from?->format('d/m/Y') ?? 'início' }} a {{ $to?->format('d/m/Y') ?? 'hoje' }}
                            @endif
                        </p>
                    </td>
                    <td style="text-align: right; vertical-align: middle;">
                        @if(!empty($logoPath) && file_exists($logoPath))
                            <img src="{{ $logoPath }}" alt="Logo" style="max-height: 44px;">
                        @endif
                    </td>
                </tr>
            </table>
        </div>

        <h2>Resumo Geral</h2>
        <table class="dados">
            <tr>
                <th>Meta</th>
                <th>Arrecadado</th>
                <th>A receber</th>
                <th>Em atraso</th>
                <th>% atingido</th>
                <th>Patrocinadores</th>
            </tr>
            <tr>
                <td class="num">{{ $metrics['goal'] > 0 ? 'R$ ' . number_format($metrics['goal'], 2, ',', '.') : '—' }}</td>
                <td class="num">R$ {{ number_format($metrics['raised'], 2, ',', '.') }}</td>
                <td class="num">R$ {{ number_format($metrics['pending'], 2, ',', '.') }}</td>
                <td class="num">R$ {{ number_format($metrics['overdue'], 2, ',', '.') }}</td>
                <td class="num">{{ number_format($metrics['progress'], 1, ',', '.') }}%</td>
                <td class="num">{{ $metrics['sponsors'] }}</td>
            </tr>
        </table>

        <h2>Arrecadação por Forma de Pagamento</h2>
        <table class="dados">
            <tr><th>Forma de pagamento</th><th class="num">Valor</th></tr>
            @forelse($byMethod as $method => $total)
                <tr>
                    <td>{{ App\Models\CampaignInstallment::PAYMENT_METHODS[$method] ?? ucfirst($method) }}</td>
                    <td class="num">R$ {{ number_format($total, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="2">Nenhum pagamento no período.</td></tr>
            @endforelse
            @if($byMethod->isNotEmpty())
                <tr class="total-row"><td>Total</td><td class="num">R$ {{ number_format($byMethod->sum(), 2, ',', '.') }}</td></tr>
            @endif
        </table>

        <h2>Patrocinadores</h2>
        <table class="dados">
            <tr>
                <th>Nome</th>
                <th>Telefone</th>
                <th class="num">Comprometido</th>
                <th class="num">Pago</th>
                <th class="num">Pendente</th>
                <th>Situação</th>
            </tr>
            @forelse($sponsorRows as $row)
                <tr>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['phone'] }}</td>
                    <td class="num">R$ {{ number_format($row['committed'], 2, ',', '.') }}</td>
                    <td class="num">R$ {{ number_format($row['paid'], 2, ',', '.') }}</td>
                    <td class="num">R$ {{ number_format($row['pending'], 2, ',', '.') }}</td>
                    <td>{{ $row['situacao'] }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Nenhum patrocinador.</td></tr>
            @endforelse
        </table>

        <h2>Extrato de Recebimentos</h2>
        <table class="dados">
            <tr>
                <th>Recibo</th>
                <th>Data</th>
                <th>Patrocinador</th>
                <th>Parcela</th>
                <th>Forma</th>
                <th class="num">Valor</th>
            </tr>
            @forelse($payments as $installment)
                <tr>
                    <td>{{ $installment->receipt_number ?? '—' }}</td>
                    <td>{{ $installment->paid_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $installment->sponsor->name }}</td>
                    <td class="num">{{ $installment->installment_number }}</td>
                    <td>{{ $installment->paymentMethodLabel() }}</td>
                    <td class="num">R$ {{ number_format((float) $installment->amount, 2, ',', '.') }}</td>
                </tr>
            @empty
                <tr><td colspan="6">Nenhum recebimento no período.</td></tr>
            @endforelse
            @if($payments->isNotEmpty())
                <tr class="total-row">
                    <td colspan="5">Total do período</td>
                    <td class="num">R$ {{ number_format((float) $payments->sum('amount'), 2, ',', '.') }}</td>
                </tr>
            @endif
        </table>

        <div class="rodape">
            Relatório gerado exclusivamente a partir dos registros da campanha "{{ $campaignName }}" —
            valores independentes da contabilidade do módulo Financeiro. ADELSS Sistema Web.
        </div>
    </div>
</body>
</html>
