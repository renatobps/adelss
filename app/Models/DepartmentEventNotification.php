<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentEventNotification extends Model
{
    protected $fillable = ['instance_id', 'instance_name', 'message', 'automatic_enabled', 'scheduled_at'];

    protected $casts = ['automatic_enabled' => 'boolean', 'scheduled_at' => 'datetime'];

    public function event()
    {
        return $this->belongsTo(DepartmentEvent::class, 'event_id');
    }
}
