<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recibo #{{ $transaction->id }}</title>
    <style>
        @page { margin: 15mm; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1e3a5f;
            margin: 0;
            padding: 0;
        }
        .recibo-card {
            border: 1.5px solid #2b6cb0;
            border-radius: 14px;
            padding: 20px 22px;
        }
        .org-header {
            border-bottom: 1px solid #93c5fd;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }
        .org-header-table { width: 100%; border-collapse: collapse; }
        .org-logo-cell { width: 78px; vertical-align: middle; padding-right: 10px; }
        .org-logo { max-width: 70px; max-height: 70px; }
        .org-info-cell { vertical-align: middle; }
        .org-name { font-size: 15px; font-weight: bold; color: #1a365d; margin-bottom: 6px; }
        .org-meta { width: 100%; border-collapse: collapse; font-size: 9px; color: #2c5282; }
        .org-addr { width: 56%; vertical-align: top; padding-right: 8px; }
        .org-right { width: 44%; text-align: right; vertical-align: top; }
        .meta-bar { width: 100%; border-collapse: collapse; background: #dbeafe; margin-bottom: 12px; }
        .meta-bar td { padding: 8px 10px; font-weight: bold; font-size: 11px; color: #1a365d; }
        .meta-bar .box {
            background: #fff;
            border: 1px solid #93c5fd;
            padding: 3px 10px;
            display: inline-block;
            min-width: 90px;
            text-align: center;
            font-size: 13px;
        }
        .receipt-title { text-align: center; font-size: 16px; font-weight: bold; margin: 8px 0 14px; }
        .receipt-body { line-height: 1.8; text-align: justify; font-size: 12px; color: #1a202c; }
        .signature-block { margin-top: 36px; text-align: center; }
        .signature-img { max-height: 52px; max-width: 200px; display: block; margin: 0 auto 4px; }
        .signature-rule { border-top: 1px solid #1a202c; width: 240px; margin: 4px auto 8px; }
        .signature-name { font-weight: bold; font-size: 11px; color: #1a202c; }
        .signature-role { font-size: 10px; color: #6b7c93; margin-top: 2px; }
    </style>
</head>
<body>
    <div class="recibo-card">
        @include('financial.transactions.partials.receipt-org-header')

        @php
            if ($transaction->type === 'receita') {
                if ($transaction->member) {
                    $fromName = strtoupper($transaction->member->name);
                } else {
                    $fromName = strtoupper($transaction->received_from_other ?? 'OUTROS');
                }
                $categoryName = strtoupper($transaction->category ? $transaction->category->name : 'RECEITA');
            } else {
                $fromName = 'ASSEMBLEIA DE DEUS DE LUZIÂNIA';
                $categoryName = strtoupper($transaction->category ? $transaction->category->name : 'DESPESA');
            }
            $amount = number_format((float) $transaction->amount, 2, ',', '.');
            $date = $transaction->transaction_date->format('d/m/Y');
            $city = 'LUZIÂNIA - GOIÁS';
            $reciboNumero = str_pad((string) $transaction->id, 6, '0', STR_PAD_LEFT);
        @endphp

        <table class="meta-bar">
            <tr>
                <td>RECIBO Nº <span class="box">{{ $reciboNumero }}</span></td>
                <td style="text-align: right;">VALOR <span class="box">R$ {{ $amount }}</span></td>
            </tr>
        </table>

        <div class="receipt-title">RECIBO</div>

        <div class="receipt-body">
            Recebi(emos) de <strong>{{ $fromName }}</strong>, a quantia de <strong>R$ {{ $amount }}</strong>, correspondente a "<strong>{{ $categoryName }}</strong>", e para clareza firmo(amos) o presente na cidade de <strong>{{ $city }}</strong> no dia <strong>{{ $date }}</strong>.
        </div>

        @include('financial.transactions.partials.receipt-signature')
    </div>
</body>
</html>
