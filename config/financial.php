<?php

return [
    'whatsapp' => [
        'dizimo_receipt_enabled' => env('FINANCIAL_WHATSAPP_DIZIMO', true),
        'expense_alert_enabled' => env('FINANCIAL_WHATSAPP_DESPESA', true),
        'due_reminder_enabled' => env('FINANCIAL_WHATSAPP_DESPESA_VENCIMENTO', true),
        'send_pdf_receipt' => env('FINANCIAL_WHATSAPP_PDF_RECEIPT', true),
        'due_reminder_days_ahead' => (int) env('FINANCIAL_DESPESA_LEMBRETE_DIAS', 1),
    ],

    'campaigns' => [
        // Dias de antecedência do lembrete de parcela de campanha a vencer
        'due_reminder_days_ahead' => (int) env('CAMPAIGN_PARCELA_LEMBRETE_DIAS', 3),
    ],

    'receipt' => [
        // Relativo a public/. Troque o arquivo para uma arte em alta resolução sem alterar o template.
        'logo' => env('FINANCIAL_RECEIPT_LOGO', 'images/logo-cdel.png'),
        'background' => env('FINANCIAL_RECEIPT_BACKGROUND', 'images/recibo-fundo.png'),
        'width_px' => 1447,
        'height_px' => 1087,
    ],
];
