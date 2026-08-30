<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo - {{ $transaction->description }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; padding: 20px; background: #f5f5f5; color: #1e3a5f; }
        .recibo-card {
            max-width: 720px;
            margin: 0 auto;
            background: #fff;
            border: 1.5px solid #2b6cb0;
            border-radius: 14px;
            padding: 22px 24px;
        }
        .org-header {
            border-bottom: 1px solid #93c5fd;
            padding-bottom: 12px;
            margin-bottom: 14px;
        }
        .org-header-table { width: 100%; border-collapse: collapse; }
        .org-logo-cell { width: 88px; vertical-align: middle; padding-right: 12px; }
        .org-logo { max-width: 76px; max-height: 76px; display: block; }
        .org-info-cell { vertical-align: middle; }
        .org-name { font-size: 18px; font-weight: 700; color: #1a365d; margin-bottom: 8px; }
        .org-meta { width: 100%; border-collapse: collapse; font-size: 11px; color: #2c5282; }
        .org-addr { width: 56%; vertical-align: top; padding-right: 10px; }
        .org-right { width: 44%; text-align: right; vertical-align: top; }
        .meta-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #dbeafe;
            padding: 8px 12px;
            margin-bottom: 16px;
            font-weight: 700;
            font-size: 13px;
            color: #1a365d;
        }
        .meta-bar .box {
            background: #fff;
            border: 1px solid #93c5fd;
            padding: 4px 12px;
            min-width: 120px;
            text-align: center;
            font-size: 16px;
        }
        .receipt-title { text-align: center; font-size: 20px; font-weight: 700; margin: 8px 0 18px; letter-spacing: .04em; }
        .receipt-body { text-align: justify; line-height: 1.85; font-size: 14px; margin: 12px 0 8px; color: #1a202c; }
        .signature-block { margin-top: 40px; text-align: center; }
        .signature-img { max-height: 56px; max-width: 220px; display: block; margin: 0 auto 4px; }
        .signature-rule { border-top: 1px solid #1a202c; width: 260px; margin: 4px auto 8px; }
        .signature-name { font-weight: 700; font-size: 13px; color: #1a202c; }
        .signature-role { font-size: 12px; color: #6b7c93; margin-top: 2px; }
        .print-buttons { text-align: center; margin-bottom: 20px; }
        .btn { padding: 10px 20px; margin: 0 5px; border: none; border-radius: 4px; cursor: pointer; font-size: 14px; }
        .btn-primary { background: #007bff; color: #fff; }
        .btn-secondary { background: #6c757d; color: #fff; }
        @media print {
            .print-buttons { display: none; }
            body { background: #fff; padding: 12mm; }
            .recibo-card { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="print-buttons">
        <button class="btn btn-primary" onclick="window.print()">Imprimir recibo</button>
        <button class="btn btn-secondary" onclick="window.close()">Fechar</button>
    </div>

    <div class="recibo-card">
        @include('financial.transactions.partials.receipt-org-header')

        @php
            if ($transaction->type === 'receita') {
                if ($transaction->member) {
                    $fromName = \App\Support\PdfText::upper($transaction->member->name);
                } else {
                    $fromName = \App\Support\PdfText::upper($transaction->received_from_other ?? 'OUTROS');
                }
                $categoryName = \App\Support\PdfText::upper($transaction->category ? $transaction->category->name : 'RECEITA');
            } else {
                $fromName = 'ASSEMBLEIA DE DEUS DE LUZIÂNIA';
                $categoryName = \App\Support\PdfText::upper($transaction->category ? $transaction->category->name : 'DESPESA');
            }
            $amount = number_format((float) $transaction->amount, 2, ',', '.');
            $date = $transaction->transaction_date->format('d/m/Y');
            $city = 'BRASÍLIA - DF';
            $reciboNumero = str_pad((string) $transaction->id, 6, '0', STR_PAD_LEFT);
        @endphp

        <div class="meta-bar">
            <div>RECIBO Nº <span class="box">{{ $reciboNumero }}</span></div>
            <div>VALOR <span class="box">R$ {{ $amount }}</span></div>
        </div>

        <div class="receipt-title">RECIBO</div>

        <div class="receipt-body">
            Recebi(emos) de <strong>{{ $fromName }}</strong>, a quantia de <strong>R$ {{ $amount }}</strong>, correspondente a "<strong>{{ $categoryName }}</strong>", e para clareza firmo(amos) o presente na cidade de <strong>{{ $city }}</strong> no dia <strong>{{ $date }}</strong>.
        </div>

        @include('financial.transactions.partials.receipt-signature')
    </div>
</body>
</html>
