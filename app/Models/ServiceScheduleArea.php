<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceScheduleArea extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'service_area_id',
        'required_quantity',
        'responsible_id',
    ];

    /**
     * Relacionamento com Escala
     */
    public function schedule()
    {
        return $this->belongsTo(ServiceSchedule::class, 'schedule_id');
    }

    /**
     * Relacionamento com Área de Serviço
     */
    public function serviceArea()
    {
        return $this->belongsTo(ServiceArea::class, 'service_area_id');
    }

    /**
     * Relacionamento com Responsável
     */
    public function responsible()
    {
        return $this->belongsTo(Member::class, 'responsible_id');
    }

    /**
     * Relacionamento com Voluntários da Área
     */
    public function volunteers()
    {
        return $this->hasMany(ServiceScheduleVolunteer::class, 'schedule_area_id');
    }
}
