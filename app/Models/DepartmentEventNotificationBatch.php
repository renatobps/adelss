<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepartmentEventNotificationBatch extends Model
{
    protected $guarded = ['id'];

    public function event()
    {
        return $this->belongsTo(DepartmentEvent::class, 'event_id');
    }
}
