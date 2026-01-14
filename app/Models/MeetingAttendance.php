<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MeetingAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'meeting_id',
        'member_id',
        'visitor_name',
        'type',
    ];

    protected $casts = [
        'type' => 'string',
    ];

    /**
     * Relacionamento com Reunião
     */
    public function meeting()
    {
        return $this->belongsTo(Meeting::class);
    }

    /**
     * Relacionamento com Membro (se for participante)
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Retorna o nome do participante/visitante
     */
    public function getNameAttribute()
    {
        if ($this->type === 'participant' && $this->member) {
            return $this->member->name;
        }
        return $this->visitor_name ?? 'Visitante sem nome';
    }
}


