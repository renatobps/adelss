<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceReportPhoto extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'service_report_id',
        'path',
        'uploaded_at',
    ];

    protected $casts = [
        'uploaded_at' => 'datetime',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(ServiceReport::class, 'service_report_id');
    }

    public function getUrlAttribute(): string
    {
        $path = ltrim(str_replace('\\', '/', (string) $this->path), '/');

        // Caminho relativo: funciona em localhost sem depender do APP_URL/ngrok
        return '/storage/' . $path;
    }
}
