<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceReportOffering extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'service_report_id',
        'payment_method',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public const METHODS = [
        'dinheiro' => 'Dinheiro',
        'pix' => 'PIX',
        'cartao' => 'Cartão',
        'outro' => 'Outro',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(ServiceReport::class, 'service_report_id');
    }
}
