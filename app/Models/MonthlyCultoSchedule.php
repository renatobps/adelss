<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyCultoSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'month',
        'year',
        'status',
    ];

    /**
     * Relacionamento com Evento (Culto)
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Relacionamento com Preletores (membros)
     */
    public function preletores()
    {
        return $this->belongsToMany(Member::class, 'monthly_culto_preletores', 'monthly_culto_schedule_id', 'member_id')
                    ->withTimestamps();
    }

    /**
     * Relacionamento com Dirigentes (membros)
     */
    public function dirigentes()
    {
        return $this->belongsToMany(Member::class, 'monthly_culto_dirigentes', 'monthly_culto_schedule_id', 'member_id')
                    ->withTimestamps();
    }

    /**
     * Relacionamento com Portaria (voluntários)
     */
    public function portaria()
    {
        return $this->belongsToMany(Volunteer::class, 'monthly_culto_portaria', 'monthly_culto_schedule_id', 'volunteer_id')
                    ->with('member')
                    ->withTimestamps();
    }

    /**
     * Relacionamento com Áreas de Serviço (genérico)
     */
    public function serviceAreaVolunteers()
    {
        return $this->belongsToMany(Volunteer::class, 'monthly_culto_service_areas', 'monthly_culto_schedule_id', 'volunteer_id')
                    ->withPivot('id', 'service_area_id', 'status')
                    ->with('member')
                    ->withTimestamps();
    }

    /**
     * Obter voluntários de uma área de serviço específica
     */
    public function getVolunteersByServiceArea($serviceAreaId)
    {
        return $this->serviceAreaVolunteers()
                    ->wherePivot('service_area_id', $serviceAreaId)
                    ->get();
    }

    /**
     * Scope para filtrar por mês e ano
     */
    public function scopeByMonthYear($query, $month, $year)
    {
        return $query->where('month', $month)->where('year', $year);
    }
}
