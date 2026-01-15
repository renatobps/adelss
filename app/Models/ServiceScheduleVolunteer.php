<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceScheduleVolunteer extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_area_id',
        'volunteer_id',
        'status',
        'notes',
    ];

    /**
     * Relacionamento com Área da Escala
     */
    public function scheduleArea()
    {
        return $this->belongsTo(ServiceScheduleArea::class, 'schedule_area_id');
    }

    /**
     * Relacionamento com Voluntário
     */
    public function volunteer()
    {
        return $this->belongsTo(Volunteer::class, 'volunteer_id');
    }
}
