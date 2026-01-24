<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'date',
        'start_time',
        'type',
        'status',
        'location',
        'notes',
        'event_id',
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'datetime:H:i',
    ];

    /**
     * Relacionamento com Evento
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Relacionamento com Áreas da Escala
     */
    public function areas()
    {
        return $this->hasMany(ServiceScheduleArea::class, 'schedule_id');
    }

    /**
     * Scope para escalas publicadas
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'publicada');
    }

    /**
     * Scope para escalas rascunho
     */
    public function scopeDraft($query)
    {
        return $query->where('status', 'rascunho');
    }

    /**
     * Relacionamento com Histórico de Serviço
     */
    public function serviceHistories()
    {
        return $this->hasMany(ServiceHistory::class, 'schedule_id');
    }

    /**
     * Scope para escalas concluídas
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'concluido');
    }
}
