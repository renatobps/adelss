<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public static function log(string $module, string $action, ?string $description = null, array $metadata = []): void
    {
        $payload = [
            'user_id' => Auth::id(),
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'metadata' => $metadata,
            'ip_address' => request()?->ip(),
        ];

        try {
            if (Schema::hasTable('audit_logs')) {
                AuditLog::create($payload);
            }
        } catch (\Throwable $e) {
            Log::warning('Falha ao gravar audit_logs', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
        }

        Log::info('Audit: '.$module.'.'.$action, [
            'description' => $description,
            'metadata' => $metadata,
            'user_id' => $payload['user_id'],
        ]);
    }
}
