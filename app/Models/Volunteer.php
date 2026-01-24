<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Volunteer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'member_id',
        'experience_level',
        'start_date',
        'status',
        'leader_notes',
    ];

    protected $casts = [
        'start_date' => 'date',
        'status' => 'string',
        'experience_level' => 'string',
    ];

    /**
     * Relacionamento com Membro
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Relacionamento muitos-para-muitos com Áreas de Serviço
     */
    public function serviceAreas()
    {
        return $this->belongsToMany(ServiceArea::class, 'volunteer_service_areas', 'volunteer_id', 'service_area_id')
                    ->withTimestamps();
    }

    /**
     * Relacionamento um-para-um com Disponibilidade
     */
    public function availability()
    {
        return $this->hasOne(VolunteerAvailability::class, 'volunteer_id');
    }

    /**
     * Relacionamento muitos-para-muitos com Eventos (via disponibilidade)
     */
    public function availabilityEvents()
    {
        return $this->belongsToMany(Event::class, 'volunteer_availability_events', 'volunteer_id', 'event_id')
                    ->withPivot('available')
                    ->withTimestamps();
    }

    /**
     * Scope para voluntários ativos
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ativo');
    }

    /**
     * Scope para buscar por membro
     */
    public function scopeByMember($query, $memberId)
    {
        return $query->where('member_id', $memberId);
    }

    /**
     * Relacionamento com Histórico de Serviço
     */
    public function serviceHistories()
    {
        return $this->hasMany(ServiceHistory::class, 'volunteer_id');
    }
}
