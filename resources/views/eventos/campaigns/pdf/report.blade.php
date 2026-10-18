{{--
    Prestação de Contas — DomPDF.
    Variáveis: $campaign, $metrics, $byMethod, $sponsorRows, $statusCounts, $payments,
               $expenses, $expensesByCategory, $from, $to, $logoPath
--}}
@php
    use App\Support\PdfText;
    use App\Models\CampaignInstallment;
    use App\Models\CampaignExpense;

    $accent = $campaign->accentColor();
    $campaignName = PdfText::stripEmoji($campaign->name);
    $departmentName = $campaign->department ? PdfText::stripEmoji($campaign->department->name) : null;
    $logo = (!empty($logoPath) && file_exists($logoPath))
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : null;

    $money = fn ($v) => 'R$ ' . number_format((float) $v, 2, ',', '.');
    $expenses = $expenses ?? collect();
    $incomes = $incomes ?? collect();
    $expensesByCategory = $expensesByCategory ?? collect();
    $statusCounts = $statusCounts ?? ['quitado' => 0, 'em_dia' => 0, 'em_atraso' => 0];
    $expensesTotal = (float) ($metrics['expenses'] ?? 0);
    $balance = (float) ($metrics['balance'] ?? ($metrics['raised'] + ($metrics['income'] ?? 0) - $expensesTotal));
    $balancePositive = $balance >= 0;
    $hasPeriod = $from || $to;
    $raisedSum = max(0.01, (float) $byMethod->sum());
    $expSum = max(0.01, (float) $expensesByCategory->sum());

    $statusClass = fn (string $key) => match ($key) {
        'quitado' => 'st-quitado',
        'em_atraso' => 'st-atraso',
        'em_dia' => 'st-dia',
        default => 'st-neutro',
    };
    $statusColor = fn (string $key) => match ($key) {
        'quitado' => '#1FA855',
        'em_atraso' => '#DC3545',
        'em_dia' => '#0088CC',
        default => '#6C757D',
    };
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<title>Prestação de Contas — {{ $campaignName }}</title>
<style>
    @page { margin: 12mm 10mm 16mm 10mm; size: A4 portrait; }
    body {
        font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
        font-size: 9.5px;
        color: #2E353E;
        margin: 0; padding: 0;
    }
    table { border-collapse: collapse; }

    /* ---------- Cabeçalho ---------- */
    .header {
        width: 100%;
        background-color: #2E353E;
        color: #FFFFFF;
        border-left: 8px solid {{ $accent }};
    }
    .header td { padding: 12px 14px; vertical-align: middle; }
    .header .kicker { font-size: 8.5px; text-transform: uppercase; letter-spacing: 1px; color: #B9C4CE; margin: 0 0 3px 0; }
    .header h1 { margin: 0; font-size: 16px; font-weight: bold; color: #FFFFFF; }
    .header .meta { margin: 5px 0 0 0; font-size: 8.5px; color: #D7DEE5; }
    .header .logo-box { text-align: right; width: 130px; }
    .header .logo-box .logo-bg { display: inline-block; background: #FFFFFF; padding: 5px 8px; border-radius: 4px; }
    .header img { height: 32px; }
    .period-bar {
        width: 100%;
        background-color: #F7F9FB;
        border: 1px solid #EEF0F2;
        border-top: 0;
        font-size: 8.5px;
        color: #6C757D;
    }
    .period-bar td { padding: 5px 14px; }
    .period-bar b { color: #2E353E; }

    /* ---------- Títulos de seção ---------- */
    h2 {
        font-size: 10.5px;
        color: #2E353E;
        text-transform: uppercase;
        letter-spacing: .6px;
        margin: 16px 0 6px 0;
        padding: 0 0 4px 0;
        border-bottom: 1.5px solid {{ $accent }};
    }
    h2 .sub { font-weight: normal; text-transform: none; letter-spacing: 0; color: #8A97A3; font-size: 8.5px; }

    /* ---------- KPIs ---------- */
    .kpis { width: 100%; margin-top: 12px; }
    .kpis td { padding: 0 3px; vertical-align: top; width: 16.66%; }
    .kpi {
        border: 1px solid #EEF0F2;
        border-top: 3px solid #0088CC;
        background: #FFFFFF;
        padding: 7px 8px;
    }
    .kpi .lbl { font-size: 7.5px; text-transform: uppercase; letter-spacing: .5px; color: #6C757D; margin-bottom: 3px; }
    .kpi .val { font-size: 11px; font-weight: bold; color: #2E353E; }
    .kpi .sub { font-size: 7.5px; color: #8A97A3; margin-top: 2px; }
    .kpi.k-meta { border-top-color: {{ $accent }}; }
    .kpi.k-arrec { border-top-color: #1FA855; } .kpi.k-arrec .val { color: #1FA855; }
    .kpi.k-receber { border-top-color: #F5A623; } .kpi.k-receber .val { color: #D9850B; }
    .kpi.k-atraso { border-top-color: #DC3545; } .kpi.k-atraso .val { color: #DC3545; }
    .kpi.k-gastos { border-top-color: #E4572E; } .kpi.k-gastos .val { color: #E4572E; }
    .kpi.k-saldo { border-top-color: {{ $balancePositive ? '#0088CC' : '#DC3545' }}; } .kpi.k-saldo .val { color: {{ $balancePositive ? '#0088CC' : '#DC3545' }}; }

    .bar { width: 100%; height: 5px; background: #EEF0F2; margin-top: 4px; }
    .bar .fill { height: 5px; }

    /* ---------- Fluxo ---------- */
    .flow { width: 100%; margin-top: 8px; background: #FBFCFD; border: 1px solid #EEF0F2; }
    .flow td { padding: 8px 6px; text-align: center; vertical-align: middle; }
    .flow .lbl { font-size: 7.5px; text-transform: uppercase; letter-spacing: .5px; color: #6C757D; }
    .flow .val { font-size: 12px; font-weight: bold; }
    .flow .op { font-size: 16px; color: #B9C4CE; width: 24px; }

    /* ---------- Tabelas de dados ---------- */
    table.dados { width: 100%; margin-bottom: 4px; }
    table.dados th {
        background-color: #2E353E;
        color: #FFFFFF;
        font-size: 7.5px;
        text-transform: uppercase;
        letter-spacing: .5px;
        padding: 5px 6px;
        text-align: left;
        border: 0;
    }
    table.dados td {
        padding: 5px 6px;
        border-bottom: 1px solid #EEF0F2;
        vertical-align: middle;
    }
    table.dados tr.zebra td { background-color: #F9FAFB; }
    table.dados tr.total td {
        background-color: #EEF0F2;
        font-weight: bold;
        border-top: 1.5px solid #B9C4CE;
        border-bottom: 0;
    }
    table.dados td.empty { text-align: center; color: #8A97A3; padding: 12px; font-style: italic; }
    .num { text-align: right; white-space: nowrap; }
    .center { text-align: center; }
    .muted { color: #8A97A3; font-size: 8px; }
    .bold { font-weight: bold; }
    .c-success { color: #1FA855; }
    .c-danger { color: #DC3545; }
    .c-warning { color: #D9850B; }
    .c-primary { color: #0088CC; }

    /* Linhas coloridas por situação (borda à esquerda) */
    table.dados tr.row-quitado td:first-child { border-left: 3px solid #1FA855; }
    table.dados tr.row-em_dia td:first-child { border-left: 3px solid #0088CC; }
    table.dados tr.row-em_atraso td:first-child { border-left: 3px solid #DC3545; }
    table.dados tr.row-em_atraso td { background-color: #FDF3F4; }
    table.dados tr.row-quitado td { background-color: #F1FAF4; }

    /* Selos de status */
    .st {
        display: inline-block;
        font-size: 7.5px;
        font-weight: bold;
        padding: 2px 7px;
        border-radius: 8px;
        color: #FFFFFF;
        white-space: nowrap;
    }
    .st-quitado { background-color: #1FA855; }
    .st-dia { background-color: #0088CC; }
    .st-atraso { background-color: #DC3545; }
    .st-neutro { background-color: #6C757D; }
    .tag {
        display: inline-block;
        font-size: 7.5px;
        padding: 2px 6px;
        border-radius: 8px;
        background-color: #EEF0F2;
        color: #2E353E;
        white-space: nowrap;
    }
    .tag-cat { background-color: #FDEFD8; color: #A56A00; }

    /* Legenda */
    .legend { margin: 4px 0 0 0; font-size: 8px; color: #6C757D; }
    .legend .st { margin-right: 4px; }

    .pdf-footer {
        position: fixed;
        bottom: -8mm; left: 0; right: 0;
        font-size: 7.5px;
        color: #8A97A3;
        border-top: 1px solid #EEF0F2;
        padding-top: 4px;
    }
    .pdf-footer .page-num:after { content: counter(page); }
    .avoid-break { page-break-inside: avoid; }

    /* ---------- Anexos: comprovantes ---------- */
    .anexos-capa { page-break-before: always; }
    .anexos-capa h2 { margin-top: 0; }
    .anexos-intro { font-size: 8.5px; color: #6C757D; margin: 0 0 8px 0; }
    .anexo { page-break-before: always; page-break-inside: avoid; }
    .anexo-head { width: 100%; background: #F7F9FB; border: 1px solid #EEF0F2; border-left: 4px solid #E4572E; margin-bottom: 6px; }
    .anexo-head td { padding: 6px 8px; vertical-align: top; }
    .anexo-head .lbl { font-size: 7px; text-transform: uppercase; letter-spacing: .5px; color: #8A97A3; }
    .anexo-head .val { font-size: 9px; color: #2E353E; font-weight: bold; }
    .anexo-head .num-badge { display: inline-block; background: #2E353E; color: #FFFFFF; font-size: 8px; font-weight: bold; padding: 3px 8px; border-radius: 8px; white-space: nowrap; }
    .anexo-img { text-align: center; }
    .anexo-img img { border: 1px solid #EEF0F2; }
    .anexo-file { font-size: 7.5px; color: #8A97A3; text-align: center; margin-top: 4px; }
    .anexo-placeholder { border: 1px dashed #B9C4CE; background: #FBFCFD; padding: 28px 16px; text-align: center; color: #6C757D; font-size: 9px; }
    .anexo-placeholder .big { font-size: 22px; font-weight: bold; color: #DC3545; margin-bottom: 6px; }
    .anexo-placeholder a { color: #0088CC; text-decoration: none; word-break: break-all; }
    table.indice td { padding: 3px 6px; border-bottom: 1px solid #EEF0F2; font-size: 8.5px; }
    table.indice th { background: #2E353E; color: #FFFFFF; font-size: 7.5px; text-transform: uppercase; padding: 4px 6px; text-align: left; }
</style>
</head>
<body>

    <div class="pdf-footer">
        <table width="100%"><tr>
            <td>Prestação de Contas — {{ $campaignName }} • ADELSS Sistema Web • Emitido em {{ now()->format('d/m/Y H:i') }}</td>
            <td style="text-align: right;">Página <span class="page-num"></span></td>
        </tr></table>
    </div>

    {{-- Cabeçalho --}}
    <table class="header">
        <tr>
            <td>
                <p class="kicker">Prestação de contas</p>
                <h1>{{ $campaignName }}</h1>
                <p class="meta">
                    @if($departmentName) Departamento: {{ $departmentName }} &nbsp;•&nbsp; @endif
                    Status: {{ ucfirst($campaign->status) }}
                    @if($campaign->start_date || $campaign->end_date)
                        &nbsp;•&nbsp; Realização: {{ $campaign->start_date?->format('d/m/Y') ?? '—' }} a {{ $campaign->end_date?->format('d/m/Y') ?? '—' }}
                    @endif
                </p>
            </td>
            <td class="logo-box">
                @if($logo)<span class="logo-bg"><img src="{{ $logo }}" alt="Logo"></span>@endif
            </td>
        </tr>
    </table>
    <table class="period-bar">
        <tr>
            <td>
                @if($hasPeriod)
                    Período dos pagamentos e transações: <b>{{ $from?->format('d/m/Y') ?? 'início' }}</b> a <b>{{ $to?->format('d/m/Y') ?? 'hoje' }}</b>
                @else
                    Período: <b>todo o histórico da campanha</b>
                @endif
            </td>
            <td style="text-align: right;">Gerado em <b>{{ now()->format('d/m/Y H:i') }}</b></td>
        </tr>
    </table>

    {{-- Indicadores --}}
    <table class="kpis">
        <tr>
            <td>
                <div class="kpi k-meta">
                    <div class="lbl">Meta</div>
                    <div class="val">{{ $metrics['goal'] > 0 ? $money($metrics['goal']) : 'Sem meta' }}</div>
                    <div class="bar"><div class="fill" style="width: {{ min(100, $metrics['progress']) }}%; background: {{ $accent }}"></div></div>
                    <div class="sub">{{ number_format($metrics['progress'], 1, ',', '.') }}% atingido</div>
                </div>
            </td>
            <td>
                <div class="kpi k-arrec">
                    <div class="lbl">Total arrecadado</div>
                    <div class="val">{{ $money($metrics['raised'] + $metrics['income']) }}</div>
                    <div class="sub">Patrocinadores: {{ $money($metrics['raised']) }}<br>Receitas extras: {{ $money($metrics['income']) }}</div>
                </div>
            </td>
            <td>
                <div class="kpi k-receber">
                    <div class="lbl">A receber</div>
                    <div class="val">{{ $money($metrics['pending']) }}</div>
                    <div class="sub">parcelas pendentes</div>
                </div>
            </td>
            <td>
                <div class="kpi k-atraso">
                    <div class="lbl">Em atraso</div>
                    <div class="val">{{ $money($metrics['overdue']) }}</div>
                    <div class="sub">{{ $statusCounts['em_atraso'] }} patrocinador(es)</div>
                </div>
            </td>
            <td>
                <div class="kpi k-gastos">
                    <div class="lbl">Gastos</div>
                    <div class="val">{{ $money($expensesTotal) }}</div>
                    <div class="sub">{{ $expenses->count() }} lançamento(s)</div>
                </div>
            </td>
            <td>
                <div class="kpi k-saldo">
                    <div class="lbl">Saldo</div>
                    <div class="val">{{ $money($balance) }}</div>
                    <div class="sub">patrocinadores + receitas − gastos</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Fluxo --}}
    <table class="flow">
        <tr>
            <td>
                <div class="lbl">Total arrecadado</div>
                <div class="val c-success">{{ $money($metrics['raised'] + $metrics['income']) }}</div>
            </td>
            <td class="op">−</td>
            <td>
                <div class="lbl">Gastos do evento</div>
                <div class="val c-danger">{{ $money($expensesTotal) }}</div>
            </td>
            <td class="op">=</td>
            <td>
                <div class="lbl">Saldo {{ $balancePositive ? 'disponível' : 'negativo' }}</div>
                <div class="val {{ $balancePositive ? 'c-primary' : 'c-danger' }}">{{ $money($balance) }}</div>
            </td>
            <td style="border-left: 1px solid #EEF0F2;">
                <div class="lbl">Patrocinadores</div>
                <div class="val">{{ $metrics['sponsors'] }}</div>
                <div class="muted" style="margin-top: 3px;">
                    <span class="st st-quitado">{{ $statusCounts['quitado'] }}</span>
                    <span class="st st-dia">{{ $statusCounts['em_dia'] }}</span>
                    <span class="st st-atraso">{{ $statusCounts['em_atraso'] }}</span>
                </div>
            </td>
        </tr>
    </table>

    {{-- Forma de pagamento + Gastos por categoria lado a lado --}}
    <table width="100%" class="avoid-break" style="margin-top: 4px;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 5px;">
                <h2>Arrecadação por forma de pagamento</h2>
                <table class="dados">
                    <tr><th>Forma</th><th class="num" style="width: 16%;">%</th><th class="num" style="width: 32%;">Valor</th></tr>
                    @forelse($byMethod as $method => $total)
                        <tr class="{{ $loop->even ? 'zebra' : '' }}">
                            <td>
                                {{ CampaignInstallment::PAYMENT_METHODS[$method] ?? ucfirst($method) }}
                                <div class="bar" style="width: 90%;"><div class="fill" style="width: {{ $total / $raisedSum * 100 }}%; background: #1FA855"></div></div>
                            </td>
                            <td class="num muted">{{ number_format($total / $raisedSum * 100, 0) }}%</td>
                            <td class="num bold">{{ $money($total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="empty">Nenhum pagamento no período.</td></tr>
                    @endforelse
                    @if($byMethod->isNotEmpty())
                        <tr class="total"><td colspan="2">Total recebido</td><td class="num c-success">{{ $money($byMethod->sum()) }}</td></tr>
                    @endif
                </table>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 5px;">
                <h2>Gastos por categoria</h2>
                <table class="dados">
                    <tr><th>Categoria</th><th class="num" style="width: 16%;">%</th><th class="num" style="width: 32%;">Valor</th></tr>
                    @forelse($expensesByCategory as $cat => $total)
                        <tr class="{{ $loop->even ? 'zebra' : '' }}">
                            <td>
                                {{ CampaignExpense::CATEGORIES[$cat] ?? ucfirst($cat) }}
                                <div class="bar" style="width: 90%;"><div class="fill" style="width: {{ $total / $expSum * 100 }}%; background: #E4572E"></div></div>
                            </td>
                            <td class="num muted">{{ number_format($total / $expSum * 100, 0) }}%</td>
                            <td class="num bold">{{ $money($total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="empty">Nenhum gasto no período.</td></tr>
                    @endforelse
                    @if($expensesByCategory->isNotEmpty())
                        <tr class="total"><td colspan="2">Total gasto</td><td class="num c-danger">{{ $money($expensesByCategory->sum()) }}</td></tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    {{-- Patrocinadores --}}
    @if($simplified ?? false)
    <h2>Patrocinadores <span class="sub">— valor pago por patrocinador</span></h2>
    <table class="dados">
        <thead>
            <tr>
                <th style="width: 6%;" class="center">#</th>
                <th>Patrocinador</th>
                <th class="num" style="width: 22%;">Pago</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sponsorRows as $row)
                <tr class="{{ $loop->even ? 'zebra' : '' }}">
                    <td class="center muted">{{ $loop->iteration }}</td>
                    <td class="bold">{{ PdfText::stripEmoji($row['name']) }}</td>
                    <td class="num c-success bold">{{ $money($row['paid']) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="empty">Nenhum patrocinador.</td></tr>
            @endforelse
        </tbody>
        @if($sponsorRows->isNotEmpty())
            <tfoot>
                <tr class="total">
                    <td colspan="2">Total pago ({{ $sponsorRows->count() }} patrocinador(es))</td>
                    <td class="num c-success">{{ $money($sponsorRows->sum('paid')) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>
    @else
    <h2>Patrocinadores <span class="sub">— situação considerando todas as parcelas da campanha</span></h2>
    <table class="dados">
        <thead>
            <tr>
                <th style="width: 28%;">Patrocinador</th>
                <th style="width: 14%;">Telefone</th>
                <th class="center" style="width: 9%;">Parcelas</th>
                <th class="num" style="width: 13%;">Comprometido</th>
                <th class="num" style="width: 12%;">Pago</th>
                <th class="num" style="width: 12%;">Pendente</th>
                <th class="center" style="width: 12%;">Situação</th>
            </tr>
        </thead>
        <tbody>
            @forelse($sponsorRows as $row)
                @php($key = $row['situacao_key'] ?? 'neutro')
                <tr class="row-{{ $key }}">
                    <td class="bold">{{ PdfText::stripEmoji($row['name']) }}</td>
                    <td class="muted">{{ $row['phone'] }}</td>
                    <td class="center">
                        {{ $row['paid_count'] ?? '' }}{{ isset($row['total_count']) ? '/' . $row['total_count'] : '' }}
                        @if(isset($row['progress']))
                            <div class="bar" style="width: 80%; margin: 3px auto 0;"><div class="fill" style="width: {{ $row['progress'] }}%; background: {{ $statusColor($key) }}"></div></div>
                        @endif
                    </td>
                    <td class="num">{{ $money($row['committed']) }}</td>
                    <td class="num c-success bold">{{ $money($row['paid']) }}</td>
                    <td class="num {{ $row['pending'] > 0 ? ($key === 'em_atraso' ? 'c-danger bold' : 'c-warning') : 'muted' }}">{{ $money($row['pending']) }}</td>
                    <td class="center"><span class="st {{ $statusClass($key) }}">{{ $row['situacao'] }}</span></td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">Nenhum patrocinador.</td></tr>
            @endforelse
        </tbody>
        @if($sponsorRows->isNotEmpty())
            <tfoot>
                <tr class="total">
                    <td colspan="3">Totais ({{ $sponsorRows->count() }} patrocinador(es))</td>
                    <td class="num">{{ $money($sponsorRows->sum('committed')) }}</td>
                    <td class="num c-success">{{ $money($sponsorRows->sum('paid')) }}</td>
                    <td class="num c-warning">{{ $money($sponsorRows->sum('pending')) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        @endif
    </table>
    <p class="legend">
        Legenda:
        <span class="st st-quitado">Quitado</span> todas as parcelas pagas &nbsp;
        <span class="st st-dia">Em dia</span> parcelas pendentes dentro do prazo &nbsp;
        <span class="st st-atraso">Em atraso</span> há parcela vencida sem pagamento
    </p>
    @endif

    {{-- Extrato de recebimentos --}}
    <h2>Extrato de recebimentos <span class="sub">— parcelas pagas em ordem cronológica</span></h2>
    <table class="dados">
        <thead>
            <tr>
                <th style="width: 14%;">Recibo</th>
                <th style="width: 15%;">Data</th>
                <th>Patrocinador</th>
                <th class="center" style="width: 9%;">Parcela</th>
                <th style="width: 13%;">Forma</th>
                <th class="num" style="width: 15%;">Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($payments as $installment)
                <tr class="{{ $loop->even ? 'zebra' : '' }}">
                    <td class="muted" style="color: #2E353E;">{{ $installment->receipt_number ?? '—' }}</td>
                    <td>{{ $installment->paid_at?->format('d/m/Y') }} <span class="muted">{{ $installment->paid_at?->format('H:i') }}</span></td>
                    <td class="bold">{{ PdfText::stripEmoji($installment->sponsor->name) }}</td>
                    <td class="center">{{ $installment->installment_number }}ª</td>
                    <td><span class="tag">{{ $installment->paymentMethodLabel() }}</span></td>
                    <td class="num c-success bold">{{ $money($installment->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty">Nenhum recebimento no período.</td></tr>
            @endforelse
        </tbody>
        @if($payments->isNotEmpty())
            <tfoot>
                <tr class="total">
                    <td colspan="5">Total recebido{{ $hasPeriod ? ' no período' : '' }} ({{ $payments->count() }} parcela(s))</td>
                    <td class="num c-success">{{ $money($payments->sum('amount')) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    {{-- Gastos --}}
    <h2>Receitas extras <span class="sub">— doações externas e outras arrecadações</span></h2>
    <table class="dados">
        <thead>
            <tr>
                <th style="width: 11%;">Data</th>
                <th>Descrição</th>
                <th style="width: 13%;">Categoria</th>
                <th style="width: 16%;">Doador / Origem</th>
                <th style="width: 11%;">Forma</th>
                <th class="center" style="width: 9%;">Comprov.</th>
                <th class="num" style="width: 14%;">Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($incomes as $income)
                <tr class="{{ $loop->even ? 'zebra' : '' }}">
                    <td>{{ $income->expense_date?->format('d/m/Y') }}</td>
                    <td>
                        <span class="bold">{{ PdfText::stripEmoji($income->description) }}</span>
                        @if($income->notes)<div class="muted">{{ \Illuminate\Support\Str::limit(PdfText::stripEmoji($income->notes), 80) }}</div>@endif
                    </td>
                    <td><span class="tag tag-cat">{{ $income->categoryLabel() }}</span></td>
                    <td>{{ $income->supplier ? PdfText::stripEmoji($income->supplier) : '—' }}</td>
                    <td>{{ $income->paymentMethodLabel() }}</td>
                    <td class="center">{{ $income->attachments->count() }}</td>
                    <td class="num c-success bold">{{ $money($income->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">Nenhuma receita extra no período.</td></tr>
            @endforelse
        </tbody>
        @if($incomes->isNotEmpty())
            <tfoot>
                <tr class="total">
                    <td colspan="6">Total de receitas extras{{ $hasPeriod ? ' no período' : '' }} ({{ $incomes->count() }} lançamento(s))</td>
                    <td class="num c-success">{{ $money($incomes->sum('amount')) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    {{-- Gastos --}}
    <h2>Gastos do evento <span class="sub">— despesas registradas com comprovante</span></h2>
    <table class="dados">
        <thead>
            <tr>
                <th style="width: 11%;">Data</th>
                <th>Descrição</th>
                <th style="width: 13%;">Categoria</th>
                <th style="width: 16%;">Fornecedor</th>
                <th style="width: 11%;">Forma</th>
                <th class="center" style="width: 9%;">Comprov.</th>
                <th class="num" style="width: 14%;">Valor</th>
            </tr>
        </thead>
        <tbody>
            @forelse($expenses as $expense)
                <tr class="{{ $loop->even ? 'zebra' : '' }}">
                    <td>{{ $expense->expense_date?->format('d/m/Y') }}</td>
                    <td>
                        <span class="bold">{{ PdfText::stripEmoji($expense->description) }}</span>
                        @if($expense->notes)<div class="muted">{{ \Illuminate\Support\Str::limit(PdfText::stripEmoji($expense->notes), 80) }}</div>@endif
                    </td>
                    <td><span class="tag tag-cat">{{ $expense->categoryLabel() }}</span></td>
                    <td>{{ $expense->supplier ? PdfText::stripEmoji($expense->supplier) : '—' }}</td>
                    <td>{{ $expense->paymentMethodLabel() }}</td>
                    <td class="center">{{ $expense->attachments->count() }}</td>
                    <td class="num c-danger bold">{{ $money($expense->amount) }}</td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">Nenhum gasto no período.</td></tr>
            @endforelse
        </tbody>
        @if($expenses->isNotEmpty())
            <tfoot>
                <tr class="total">
                    <td colspan="6">Total de gastos{{ $hasPeriod ? ' no período' : '' }} ({{ $expenses->count() }} lançamento(s))</td>
                    <td class="num c-danger">{{ $money($expenses->sum('amount')) }}</td>
                </tr>
            </tfoot>
        @endif
    </table>

    {{-- Fechamento --}}
    <table class="flow avoid-break" style="margin-top: 14px;">
        <tr>
            <td style="text-align: left; width: 60%;">
                <div class="lbl">Fechamento</div>
                <div style="font-size: 8.5px; color: #6C757D; margin-top: 3px;">
                    Relatório gerado exclusivamente a partir dos registros da campanha "{{ $campaignName }}" —
                    valores independentes da contabilidade do módulo Financeiro.
                </div>
            </td>
            <td>
                <div class="lbl">Saldo final</div>
                <div class="val {{ $balancePositive ? 'c-primary' : 'c-danger' }}" style="font-size: 14px;">{{ $money($balance) }}</div>
            </td>
        </tr>
    </table>

    {{-- Anexos: comprovantes dos gastos --}}
    @php($receipts = $receipts ?? [])
    @if(count($receipts) > 0)
        <div class="anexos-capa">
            <h2>Anexos — comprovantes das transações <span class="sub">— {{ count($receipts) }} comprovante(s) de {{ $expenses->count() + $incomes->count() }} transação(ões)</span></h2>
            <p class="anexos-intro">
                Cada comprovante (nota fiscal, recibo ou foto) é apresentado em uma folha, identificado pela transação a que pertence.
                A numeração abaixo corresponde à ordem dos anexos.
            </p>
            <table class="indice" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 7%;">Anexo</th>
                        <th style="width: 12%;">Data</th>
                        <th>Transação</th>
                        <th style="width: 16%;">Categoria</th>
                        <th style="width: 20%;">Arquivo</th>
                        <th class="num" style="width: 14%;">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($receipts as $r)
                        <tr>
                            <td class="bold">{{ $loop->iteration }}</td>
                            <td>{{ $r['expense']->expense_date?->format('d/m/Y') }}</td>
                            <td>{{ PdfText::stripEmoji($r['expense']->description) }}@if($r['total'] > 1) <span class="muted">({{ $r['index'] }}/{{ $r['total'] }})</span>@endif</td>
                            <td>{{ $r['expense']->categoryLabel() }}</td>
                            <td class="muted">{{ \Illuminate\Support\Str::limit($r['attachment']->file_name, 28) }}</td>
                            <td class="num {{ $r['expense']->isIncome() ? 'c-success' : 'c-danger' }} bold">{{ $money($r['expense']->amount) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @foreach($receipts as $r)
            @php($exp = $r['expense'])
            <div class="anexo">
                <table class="anexo-head">
                    <tr>
                        <td style="width: 12%; text-align: center; vertical-align: middle;">
                            <span class="num-badge">Anexo {{ $loop->iteration }}</span>
                        </td>
                        <td style="width: 31%;">
                            <div class="lbl">{{ $exp->kindLabel() }}</div>
                            <div class="val">{{ PdfText::stripEmoji($exp->description) }}</div>
                        </td>
                        <td style="width: 13%;">
                            <div class="lbl">Data</div>
                            <div class="val">{{ $exp->expense_date?->format('d/m/Y') }}</div>
                        </td>
                        <td style="width: 16%;">
                            <div class="lbl">Categoria</div>
                            <div class="val">{{ $exp->categoryLabel() }}</div>
                        </td>
                        <td style="width: 14%;">
                            <div class="lbl">{{ $exp->isIncome() ? 'Doador / Origem' : 'Fornecedor' }}</div>
                            <div class="val">{{ $exp->supplier ? PdfText::stripEmoji($exp->supplier) : '—' }}</div>
                        </td>
                        <td style="width: 14%; text-align: right;">
                            <div class="lbl">Valor</div>
                            <div class="val {{ $exp->isIncome() ? 'c-success' : 'c-danger' }}">{{ $money($exp->amount) }}</div>
                        </td>
                    </tr>
                </table>

                @if($r['embeddable'])
                    <div class="anexo-img">
                        <img src="{{ $r['src'] }}" alt="Comprovante" style="width: {{ $r['width_mm'] }}mm; height: {{ $r['height_mm'] }}mm;">
                    </div>
                @else
                    <div class="anexo-placeholder">
                        <div class="big">{{ $r['attachment']->isImage() ? 'IMG' : 'PDF' }}</div>
                        <div class="bold" style="color: #2E353E; margin-bottom: 4px;">{{ $r['attachment']->file_name }}</div>
                        <div>{{ $r['reason'] }}</div>
                        <div style="margin-top: 8px;">
                            <a href="{{ route('eventos.campaigns.expenses.attachment', $r['attachment']) }}">{{ route('eventos.campaigns.expenses.attachment', $r['attachment']) }}</a>
                        </div>
                    </div>
                @endif

                <div class="anexo-file">
                    {{ $r['attachment']->file_name }} • {{ $r['attachment']->humanSize() }}
                    @if($r['total'] > 1) • comprovante {{ $r['index'] }} de {{ $r['total'] }} desta transação @endif
                </div>
            </div>
        @endforeach
    @endif

</body>
</html>
