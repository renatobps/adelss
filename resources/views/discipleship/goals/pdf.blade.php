<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Propósito de Discipulado - {{ $goal->descricao }}</title>

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            margin-left: 20px;
            margin-right: 20px;
            margin-top: 20px;
            margin-bottom: 20px;
            color: #2c3e50;
            line-height: 1.45;
            background: #fff;
        }

        @page {
            margin: 15mm;
        }

        /* ========== HEADER ========== */
        .header {
            background: #1a365d;
            padding: 8px 12px;
            
            margin-bottom: 12px;
        }
        .header-table { width: 100%; border-collapse: collapse; }
        .header-table td { vertical-align: middle; }
        .header-table .logo-cell { width: 1%; white-space: nowrap; }
        .header-table .text-cell { text-align: right; padding-left: 12px; }
        .header img {
            max-height: 45px;
            max-width: 120px;
            display: block;
        }
        .header .church-name {
            font-size: 14px;
            font-weight: bold;
            color: #fff;
            margin: 0 0 2px 0;
        }
        .header .doc-title {
            font-size: 10px;
            color: #fff;
            font-weight: normal;
            margin: 0;
            opacity: 0.95;
        }
        .member-badge {
            font-size: 9px;
            color: #64748b;
            margin-bottom: 12px;
        }
        .member-badge span { font-weight: bold; color: #1e293b; }

        /* ========== HERO / TÍTULO ========== */
        .hero {
            background: #1a365d;
            color: #fff;
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 18px;
            text-align: center;
        }
        .hero h1 {
            font-size: 15px;
            margin-bottom: 4px;
            font-weight: bold;
        }
        .hero p {
            font-size: 10px;
            opacity: 0.9;
        }

        /* ========== CARDS POR ÁREA ========== */
        .card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            margin-bottom: 12px;
            overflow: hidden;
        }

        .card-title {
            font-size: 11px;
            font-weight: bold;
            color: #fff;
            padding: 8px 14px;
            margin: 0;
        }

        .card-body {
            padding: 12px 14px;
            font-size: 10px;
            line-height: 1.5;
        }

        .card-body .info-row {
            margin-bottom: 6px;
        }
        .card-body .info-row:last-child { margin-bottom: 0; }
        .card-body .label {
            font-weight: bold;
            color: #475569;
            display: inline;
        }

        .card-body ul {
            margin: 6px 0 0 18px;
        }
        .card-body li {
            margin-bottom: 2px;
        }

        /* Cores por área */
        .card.proposito .card-title { background: #1a365d; }
        .card.jejum .card-title { background: #dc2626; }
        .card.oracao .card-title { background: #059669; }
        .card.estudo .card-title { background: #2563eb; }
        .card.observacao .card-title { background: #7c3aed; }

        /* Observação - formatação rica (Quill) */
        .card.observacao .card-body p { margin-bottom: 6px; }
        .card.observacao .card-body p:last-child { margin-bottom: 0; }
        .card.observacao .card-body ul, .card.observacao .card-body ol {
            margin: 4px 0 4px 14px;
            padding-left: 8px;
        }
        .card.observacao .card-body li { margin-bottom: 2px; }
        .card.observacao .card-body .ql-align-center,
        .card.observacao .card-body [style*="text-align: center"] { text-align: center; }
        .card.observacao .card-body .ql-align-right,
        .card.observacao .card-body [style*="text-align: right"] { text-align: right; }
        .card.observacao .card-body .ql-align-justify,
        .card.observacao .card-body [style*="text-align: justify"] { text-align: justify; }
        .card.observacao .card-body table {
            border-collapse: collapse;
            width: 100%;
            margin: 6px 0;
            font-size: 9px;
        }
        .card.observacao .card-body th,
        .card.observacao .card-body td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
        }
        .card.observacao .card-body th {
            background-color: #f1f5f9;
            font-weight: bold;
        }

        /* Linha em 2 colunas */
        .cards-row { width: 100%; border-collapse: collapse; table-layout: fixed; margin-bottom: 12px; }
        .cards-row td { width: 50%; vertical-align: top; padding: 0 6px 0 0; }
        .cards-row td:last-child { padding: 0 0 0 6px; }

        /* ========== FOOTER ========== */
        .footer {
            margin-top: 20px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
        }
        .footer strong { color: #64748b; }
    </style>
</head>
<body>

    <div class="header">
        <table class="header-table">
            <tr>
                <td class="logo-cell">
                    @if($logoBase64 ?? false)
                        <img src="{{ $logoBase64 }}" alt="Logo">
                    @endif
                </td>
                <td class="text-cell">
                    <div class="church-name">{{ $churchName ?? 'ADELSS' }}</div>
                    <div class="doc-title">Propósito de Discipulado</div>
                </td>
            </tr>
        </table>
    </div>
    <div class="member-badge">
        <span>{{ $goal->discipleshipMember->member->name ?? '' }}</span>
        @if($goal->discipleshipMember->cycle ?? null)
            • {{ $goal->discipleshipMember->cycle->nome }}
        @endif
        @if($goal->discipleshipMember->discipulador ?? null)
            • Discipulador: {{ $goal->discipleshipMember->discipulador->name }}
        @endif
    </div>

    <div class="hero">
        <h1>{{ $goal->descricao }}</h1>
        <p>
            @if($goal->prazo ?? null)
                Prazo: {{ $goal->prazo->format('d/m/Y') }}
            @endif
            @if($goal->status ?? null)
                • Status: {{ $goal->status === 'concluido' ? 'Concluído' : ($goal->status === 'pausado' ? 'Pausado' : 'Em andamento') }}
            @endif
        </p>
    </div>

    @php
        $restricoesLabels = [
            'filmes' => 'Filmes', 'series' => 'Séries', 'instagram' => 'Instagram',
            'youtube' => 'YouTube', 'tiktok' => 'TikTok', 'facebook' => 'Facebook'
        ];
        $alimentosLabels = [
            'derivados_trigo' => 'Derivados de trigo', 'guloseimas' => 'Guloseimas',
            'almoco' => 'Almoço', 'jantar' => 'Jantar', 'cafe_manha' => 'Café da manhã'
        ];
    @endphp

    @php
        $temProposito = $goal->quantidade_dias || ($goal->restricoes && count($goal->restricoes) > 0);
        $temJejum = $goal->tipo_jejum && $goal->tipo_jejum !== 'nenhum';
    @endphp

    <table class="cards-row">
        <tr>
            <td>
                @if($temProposito)
                <div class="card proposito">
                    <h3 class="card-title">Área de Propósito</h3>
                    <div class="card-body">
                        @if($goal->quantidade_dias)
                        <div class="info-row">
                            <span class="label">Quantidade de dias:</span> {{ $goal->quantidade_dias }} dias
                        </div>
                        @endif
                        @if($goal->restricoes && count($goal->restricoes) > 0)
                        <div class="info-row">
                            <span class="label">Restrições durante o propósito:</span>
                            <ul>
                                @foreach($goal->restricoes as $restricao)
                                    <li>{{ $restricoesLabels[$restricao] ?? $restricao }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </td>
            <td>
                @if($temJejum)
                <div class="card jejum">
                    <h3 class="card-title">Área de Jejum</h3>
                    <div class="card-body">
                        @if($goal->tipo_jejum === 'total')
                        <div class="info-row">
                            <span class="label">Tipo:</span> Jejum Total
                        </div>
                        @if($goal->horas_jejum_total)
                        <div class="info-row">
                            <span class="label">Quantidade de horas:</span> {{ $goal->horas_jejum_total }} horas
                        </div>
                        @endif
                        @elseif($goal->tipo_jejum === 'parcial')
                        <div class="info-row">
                            <span class="label">Tipo:</span> Jejum Parcial
                        </div>
                        @if($goal->dias_jejum_parcial)
                        <div class="info-row">
                            <span class="label">Quantidade de dias:</span> {{ $goal->dias_jejum_parcial }} dias
                        </div>
                        @endif
                        @if($goal->alimentos_retirados && count($goal->alimentos_retirados) > 0)
                        <div class="info-row">
                            <span class="label">Alimentos retirados:</span>
                            <ul>
                                @foreach($goal->alimentos_retirados as $alimento)
                                    <li>{{ $alimentosLabels[$alimento] ?? $alimento }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        @endif
                    </div>
                </div>
                @endif
            </td>
        </tr>
    </table>

    @php
        $temOracao = $goal->periodos_oracao_dia || $goal->minutos_oracao_periodo;
        $temEstudo = $goal->livro_biblia || $goal->capitulos_por_dia;
    @endphp

    <table class="cards-row">
        <tr>
            <td>
                @if($temOracao)
                <div class="card oracao">
                    <h3 class="card-title">Área de Oração</h3>
                    <div class="card-body">
                        @if($goal->periodos_oracao_dia)
                        <div class="info-row">
                            <span class="label">Período de oração por dia:</span>
                            {{ $goal->periodos_oracao_dia }} {{ $goal->periodos_oracao_dia == 1 ? 'vez' : 'vezes' }} ao dia
                        </div>
                        @endif
                        @if($goal->minutos_oracao_periodo)
                        <div class="info-row">
                            <span class="label">Minutos por período:</span> {{ $goal->minutos_oracao_periodo }} minutos
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </td>
            <td>
                @if($temEstudo)
                <div class="card estudo">
                    <h3 class="card-title">Área de Estudo da Palavra</h3>
                    <div class="card-body">
                        @if($goal->livro_biblia)
                        <div class="info-row">
                            <span class="label">Livro a ser estudado:</span> {{ $goal->livro_biblia }}
                        </div>
                        @endif
                        @if($goal->capitulos_por_dia)
                        <div class="info-row">
                            <span class="label">Capítulos por dia:</span>
                            {{ $goal->capitulos_por_dia }} {{ $goal->capitulos_por_dia == 1 ? 'capítulo' : 'capítulos' }}
                        </div>
                        @endif
                    </div>
                </div>
                @endif
            </td>
        </tr>
    </table>

    <!-- Observação (usa versão processada para PDF - emojis configuráveis) -->
    @if($observacaoForPdf ?? $goal->observacao)
    <div class="card observacao">
        <h3 class="card-title">Observação</h3>
        <div class="card-body">
            {!! $observacaoForPdf ?? $goal->observacao !!}
        </div>
    </div>
    @endif

    <div class="footer">
        Documento gerado em <strong>{{ ($generatedAt ?? now())->format('d/m/Y H:i') }}</strong>
        — {{ $churchName ?? 'ADELSS' }}
    </div>

</body>
</html>
