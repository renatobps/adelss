<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceReportSpiritualDecision extends Model
{
    protected $fillable = [
        'service_report_id',
        'type',
        'person_name',
        'member_id',
        'notes',
    ];

    public const TYPES = [
        'batismo_espirito' => 'Batismo no Espírito Santo',
        'cura' => 'Cura',
        'decisao_por_cristo' => 'Decisão por Cristo',
        'outro' => 'Outro',
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
