<?php

return [
    'api_url' => env('WHATSAPP_API_URL', '') ?: '',
    'client_token' => env('WHATSAPP_CLIENT_TOKEN', '') ?: '',
    'instance_id' => env('WHATSAPP_INSTANCE_ID', '') ?: '',
    'instance_token' => env('WHATSAPP_INSTANCE_TOKEN', '') ?: '',
    'instance_name' => env('WHATSAPP_INSTANCE_NAME', ''),
    'webhook_url' => env('WHATSAPP_WEBHOOK_URL', ''),
    'default_delay' => (int) env('WHATSAPP_DEFAULT_DELAY', 700),
    'timeout' => (int) env('WHATSAPP_TIMEOUT', 120),
    'max_retries' => (int) env('WHATSAPP_MAX_RETRIES', 3),
];
