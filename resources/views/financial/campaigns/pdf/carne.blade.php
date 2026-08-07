{{--
    Carnê de Campanha — DomPDF (v2: compacto, 4 parcelas por folha, margem em todas as páginas).
    Variáveis esperadas:
      $campaign -> Campaign (name, receipt_message, department, installments_count)
      $groups   -> array de ['sponsor' => CampaignSponsor, 'installments' => Collection]
                   (1 grupo = carnê individual; N grupos = carnês em lote)
      $accent   -> string opcional (hex); default: cor da campanha/departamento
--}}
@php
    use App\Support\PdfText;

    $accent = $accent ?? $campaign->accentColor();
    $campaignName = PdfText::stripEmoji($campaign->name);
    $receiptMessage = PdfText::stripEmoji($campaign->receipt_message);
    $departmentName = $campaign->department ? PdfText::stripEmoji($campaign->department->name) : null;

    $logoPath = public_path('img/img/LOG SS AZUL.png');
    $logo = file_exists($logoPath)
        ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath))
        : null;
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
    /* ===== A margem fica SÓ no @page: assim vale para TODAS as páginas.
            NUNCA definir margem também no body (o DomPDF soma as duas). ===== */
    @page { margin: 10mm 9mm; size: A4 portrait; }

    body {
        margin: 0;
        padding: 0;
        font-family: "DejaVu Sans", sans-serif;
        color: #2E353E;
        font-size: 8.5px;
        line-height: 1.3;
    }

    .ticket {
        width: 100%;
        border: 1px solid #D8DEE4;
        border-radius: 5px;
        margin-bottom: 3.5mm;
        page-break-inside: avoid;
    }
    .ticket table { width: 100%; border-collapse: collapse; }
    .ticket td { vertical-align: top; }

    .stub {
        width: 33%;
        background: #F7F9FB;
        border-right: 2px dashed #B9C4CE;
        padding: 3mm;
    }
    .receipt { padding: 3mm 3.5mm; }

    .kicker {
        font-size: 6px;
        letter-spacing: .8px;
        text-transform: uppercase;
        color: #8A97A3;
        margin: 0 0 0.5px;
    }
    .val { font-size: 8.5px; font-weight: bold; }
    .field { margin-bottom: 1.6mm; }

    .stub-title {
        font-size: 6.5px;
        font-weight: bold;
        letter-spacing: 1.5px;
        text-transform: uppercase;
        margin: 0 0 1.8mm;
    }

    .amount-pill {
        display: inline-block;
        border: 1.5px solid;
        border-radius: 14px;
        padding: 1.2mm 3.5mm;
        font-size: 13px;
        font-weight: bold;
        margin-bottom: 2mm;
    }

    .campaign-name {
        font-size: 10.5px;
        font-weight: bold;
        letter-spacing: .4px;
        text-transform: uppercase;
        margin: 0;
    }
    .sponsor-name { font-size: 10px; font-weight: bold; margin: 0 0 0.8mm; }
    .summary { font-size: 8.5px; margin: 0 0 1.8mm; }

    .verse {
        border-left: 2.5px solid;
        padding: 0.8mm 0 0.8mm 2.2mm;
        font-style: italic;
        font-size: 7px;
        color: #5A6672;
        margin: 0 0 2.2mm;
    }

    .pix-box {
        border: 1px solid #D8DEE4;
        border-radius: 3px;
        background: #F7F9FB;
        padding: 1.2mm 2.2mm;
        font-size: 7px;
        margin: 0 0 2mm;
    }
    .pix-box .pix-label {
        font-weight: bold;
        letter-spacing: .8px;
        text-transform: uppercase;
        font-size: 6.5px;
    }

    .sign-line { border-bottom: 1px solid #99A5B1; height: 4.5mm; width: 60%; }
    .sign-label { font-size: 6.5px; color: #8A97A3; padding-top: 0.6mm; }

    .paid-stamp {
        display: inline-block;
        border: 1.5px solid #1FA855;
        color: #1FA855;
        font-size: 9px;
        font-weight: bold;
        letter-spacing: 1.5px;
        padding: 1mm 3mm;
        border-radius: 3px;
    }
    .paid-info { font-size: 6.5px; color: #1FA855; padding-top: 0.6mm; }

    .logo { width: 30px; }
    .doc-head { font-size: 6.5px; color: #8A97A3; padding-bottom: 2.5mm; }
    .cut-note { font-size: 6px; color: #B9C4CE; text-align: center; padding-top: 1mm; }
    .sponsor-break { page-break-before: always; }
</style>
</head>
<body>

    @foreach($groups as $groupIndex => $group)
        @php
            $sponsor = $group['sponsor'];
            $installments = $group['installments'];
            $sponsorName = PdfText::stripEmoji($sponsor->name);
        @endphp

        @if($groupIndex > 0)
            <div class="sponsor-break"></div>
        @endif

        {{-- Cabeçalho (uma vez, no topo do carnê de cada patrocinador) --}}
        <table class="doc-head">
            <tr>
                <td>
                    <strong style="font-size:9px; color:{{ $accent }};">{{ $campaignName }}</strong> &bull;
                    Carn&ecirc; de contribui&ccedil;&atilde;o &bull; {{ $sponsorName }}
                    @if($departmentName) &bull; {{ $departmentName }} @endif
                </td>
                <td style="text-align:right; width:45px;">
                    @if($logo)<img src="{{ $logo }}" class="logo" alt="">@endif
                </td>
            </tr>
        </table>

        @foreach($installments as $inst)
            <div class="ticket">
                <table>
                    <tr>
                        {{-- ========== CANHOTO ========== --}}
                        <td class="stub">
                            <p class="stub-title" style="color:{{ $accent }};">Canhoto</p>

                            <div class="amount-pill" style="border-color:{{ $accent }}; color:{{ $accent }};">
                                R$ {{ number_format((float) $inst->amount, 2, ',', '.') }}
                            </div>

                            <div class="field">
                                <p class="kicker">Campanha</p>
                                <span class="val">{{ $campaignName }}</span>
                            </div>
                            <div class="field">
                                <p class="kicker">Patrocinador</p>
                                <span class="val">{{ $sponsorName }}</span>
                            </div>

                            <table>
                                <tr>
                                    <td style="width:38%;">
                                        <p class="kicker">Parcela</p>
                                        <span class="val">{{ $inst->installment_number }}/{{ $campaign->installments_count }}</span>
                                    </td>
                                    <td style="width:32%;">
                                        <p class="kicker">Vencto.</p>
                                        <span class="val">{{ $inst->due_date ? $inst->due_date->format('d/m/Y') : '--/--/----' }}</span>
                                    </td>
                                    <td>
                                        <p class="kicker">Pagto.</p>
                                        @if($inst->status === 'pago')
                                            <span class="val" style="color:#1FA855;">{{ $inst->paid_at ? $inst->paid_at->format('d/m/Y') : '--' }}</span>
                                        @else
                                            <span class="val" style="color:#B9C4CE;">__/__/____</span>
                                        @endif
                                    </td>
                                </tr>
                            </table>
                        </td>

                        {{-- ========== RECIBO ========== --}}
                        <td class="receipt">
                            <table>
                                <tr>
                                    <td>
                                        <p class="campaign-name" style="color:{{ $accent }};">{{ $campaignName }}</p>
                                        @if($inst->receipt_number)
                                            <p class="kicker">Recibo n&ordm; {{ $inst->receipt_number }}</p>
                                        @endif
                                    </td>
                                    <td style="text-align:right; width:45px;">
                                        @if($logo)<img src="{{ $logo }}" class="logo" alt="">@endif
                                    </td>
                                </tr>
                            </table>

                            <div style="padding-top:1.5mm;">
                                <p class="kicker">Patrocinador</p>
                                <p class="sponsor-name">{{ $sponsorName }}</p>

                                <p class="summary">
                                    Parcela <strong>{{ $inst->installment_number }}/{{ $campaign->installments_count }}</strong>
                                    &bull; <strong style="color:{{ $accent }};">R$ {{ number_format((float) $inst->amount, 2, ',', '.') }}</strong>
                                    @if($inst->due_date)
                                        &bull; vence em <strong>{{ $inst->due_date->format('d/m/Y') }}</strong>
                                    @endif
                                    @if($departmentName) &bull; {{ $departmentName }} @endif
                                </p>

                                @if($receiptMessage)
                                    <p class="verse" style="border-color:{{ $accent }};">{{ $receiptMessage }}</p>
                                @endif

                                @if($campaign->pix_key && $inst->status !== 'pago')
                                    <div class="pix-box">
                                        <span class="pix-label" style="color:{{ $accent }};">Pague com PIX</span> &mdash;
                                        Chave: <strong>{{ $campaign->pix_key }}</strong>
                                        @if($campaign->pix_recipient)
                                            &bull; Recebedor: <strong>{{ PdfText::stripEmoji($campaign->pix_recipient) }}</strong>
                                        @endif
                                    </div>
                                @endif

                                @if($inst->status === 'pago')
                                    <span class="paid-stamp">PAGO</span>
                                    <p class="paid-info">
                                        Recebido em {{ $inst->paid_at ? $inst->paid_at->format('d/m/Y') : '' }}
                                        @if($inst->payment_method) &bull; {{ $inst->paymentMethodLabel() }} @endif
                                    </p>
                                @else
                                    <div class="sign-line"></div>
                                    <p class="sign-label">Assinatura do respons&aacute;vel &bull; Data __/__/____</p>
                                @endif
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        @endforeach

        <p class="cut-note">Recorte na linha tracejada &bull; O canhoto permanece com a igreja</p>
    @endforeach
</body>
</html>
