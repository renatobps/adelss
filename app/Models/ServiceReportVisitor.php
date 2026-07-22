<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceReportVisitor extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'service_report_id',
        'name',
        'phone',
        'invited_by_member_id',
        'converted_to_member_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(ServiceReport::class, 'service_report_id');
    }

    public function invitedBy(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'invited_by_member_id');
    }

    public function convertedMember(): BelongsTo
    {
        return $this->belongsTo(Member::class, 'converted_to_member_id');
    }
}
