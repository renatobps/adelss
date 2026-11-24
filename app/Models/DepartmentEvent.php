<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentEvent extends Model
{
    protected $fillable = ['title', 'description', 'location', 'start_date', 'end_date', 'all_day', 'cost', 'banner_image'];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'all_day' => 'boolean',
        'cost' => 'decimal:2',
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function getBannerUrlAttribute(): ?string
    {
        return Event::publicStorageUrl($this->banner_image);
    }

    public function participants()
    {
        return $this->belongsToMany(Member::class, 'department_event_participants', 'event_id', 'member_id')->withTimestamps();
    }

    public function notification()
    {
        return $this->hasOne(DepartmentEventNotification::class, 'event_id');
    }

    public function notificationBatches()
    {
        return $this->hasMany(DepartmentEventNotificationBatch::class, 'event_id');
    }
}
