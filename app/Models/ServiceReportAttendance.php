<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceReportAttendance extends Model
{
    protected $fillable = [
        'service_report_id',
        'member_id',
        'present',
    ];

    protected $casts = [
        'present' => 'boolean',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(ServiceReport::class, 'service_report_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
