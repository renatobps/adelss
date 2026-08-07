<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class EventRegistration extends Model
{
    public const STATUS_PENDENTE = 'pendente';

    public const STATUS_CONFIRMADO = 'confirmado';

    public const STATUS_CANCELADO = 'cancelado';

    protected $fillable = [
        'event_id',
        'name',
        'email',
        'phone',
        'address',
        'custom_answers',
        'status',
        'registration_number',
        'check_in_token',
        'receipt_sent_at',
        'checked_in_at',
        'checked_in_by',
    ];

    protected $casts = [
        'custom_answers' => 'array',
        'receipt_sent_at' => 'datetime',
        'checked_in_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(EventRegistrationPayment::class, 'event_registration_id');
    }

    public function checkedInBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }

    public function isPaymentApproved(): bool
    {
        if (!$this->event?->is_paid) {
            return true;
        }

        return strtolower((string) ($this->payment?->status ?? '')) === 'approved';
    }
}
