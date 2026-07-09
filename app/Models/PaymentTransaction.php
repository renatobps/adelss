<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'financial_transaction_id',
        'gateway',
        'idempotency_key',
        'external_payment_id',
        'external_reference',
        'status',
        'status_detail',
        'payment_method',
        'amount',
        'currency',
        'payer_email',
        'payer_document',
        'qr_code_base64',
        'qr_code_text',
        'paid_at',
        'raw_payload',
        'webhook_payload',
        'error_message',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'raw_payload' => 'array',
        'webhook_payload' => 'array',
    ];

    public function financialTransaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class);
    }
}
