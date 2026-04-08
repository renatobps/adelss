<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    protected $casts = [
        'custom_answers' => 'array',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
