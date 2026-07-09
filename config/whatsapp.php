<?php

return [
    'api_url' => env('WHATSAPP_API_URL', '') ?: '',
    'api_key' => env('WHATSAPP_API_KEY', env('WHATSAPP_GLOBAL_API_KEY', '')) ?: '',
    'instance_name' => env('WHATSAPP_INSTANCE_NAME', ''),
    'number' => env('WHATSAPP_NUMBER', ''),
    'webhook_url' => env('WHATSAPP_WEBHOOK_URL', ''),
    'default_delay' => (int) env('WHATSAPP_DEFAULT_DELAY', 700),
    'timeout' => (int) env('WHATSAPP_TIMEOUT', 120),
    'max_retries' => (int) env('WHATSAPP_MAX_RETRIES', 3),
];
