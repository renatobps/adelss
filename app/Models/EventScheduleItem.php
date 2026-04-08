<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventScheduleItem extends Model
{
    protected $fillable = [
        'event_id',
        'title',
        'detail',
        'responsible_name',
        'responsible_photo_path',
        'time_hh',
        'time_mm',
        'sort_order',
    ];

    protected $casts = [
        'time_hh' => 'integer',
        'time_mm' => 'integer',
        'sort_order' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }
}
