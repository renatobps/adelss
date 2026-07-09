<?php

return [
    'whatsapp' => [
        'dizimo_receipt_enabled' => env('FINANCIAL_WHATSAPP_DIZIMO', true),
        'expense_alert_enabled' => env('FINANCIAL_WHATSAPP_DESPESA', true),
        'due_reminder_enabled' => env('FINANCIAL_WHATSAPP_DESPESA_VENCIMENTO', true),
        'send_pdf_receipt' => env('FINANCIAL_WHATSAPP_PDF_RECEIPT', true),
        'due_reminder_days_ahead' => (int) env('FINANCIAL_DESPESA_LEMBRETE_DIAS', 1),
    ],
];
