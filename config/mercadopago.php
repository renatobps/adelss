<?php

return [
    'access_token' => env('MP_ACCESS_TOKEN', ''),
    'public_key' => env('MP_PUBLIC_KEY', ''),
    'webhook_secret' => env('MP_WEBHOOK_SECRET', ''),
    'statement_descriptor' => env('MP_STATEMENT_DESCRIPTOR', 'ADELSS'),
    'currency' => env('MP_CURRENCY', 'BRL'),
    'notification_url' => env('MP_NOTIFICATION_URL', ''),
    'sandbox' => filter_var(env('MP_SANDBOX', true), FILTER_VALIDATE_BOOL),
    // Opcional (homologação): usar pagador fixo de teste sem pedir no formulário.
    'test_payer_email' => env('MP_TEST_PAYER_EMAIL', ''),
    'test_payer_document' => env('MP_TEST_PAYER_DOCUMENT', ''),
];
