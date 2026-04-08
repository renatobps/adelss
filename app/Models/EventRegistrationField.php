<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRegistrationField extends Model
{
    protected $fillable = [
        'event_id',
        'name',
        'field_type',
        'required',
        'options',
        'sort_order',
    ];

    protected $casts = [
        'required' => 'boolean',
        'options' => 'array',
        'sort_order' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
