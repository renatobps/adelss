<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'volunteer_id',
        'service_area_id',
        'schedule_id',
        'date',
        'service_type',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Relacionamento com Membro
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Relacionamento com Voluntário
     */
    public function volunteer()
    {
        return $this->belongsTo(Volunteer::class);
    }

    /**
     * Relacionamento com Área de Serviço
     */
    public function serviceArea()
    {
        return $this->belongsTo(ServiceArea::class, 'service_area_id');
    }

    /**
     * Relacionamento com Escala
     */
    public function schedule()
    {
        return $this->belongsTo(ServiceSchedule::class, 'schedule_id');
    }

    /**
     * Scope para serviços realizados
     */
    public function scopeServed($query)
    {
        return $query->where('status', 'serviu');
    }

    /**
     * Scope para período específico
     */
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope para voluntário específico
     */
    public function scopeForVolunteer($query, $volunteerId)
    {
        return $query->where('volunteer_id', $volunteerId);
    }

    /**
     * Scope para área específica
     */
    public function scopeForArea($query, $areaId)
    {
        return $query->where('service_area_id', $areaId);
    }
}
