<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Meeting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pgi_id',
        'meeting_date',
        'subject',
        'total_value',
        'participants_count',
        'visitors_count',
        'notes',
    ];

    protected $casts = [
        'meeting_date' => 'date',
        'total_value' => 'decimal:2',
        'participants_count' => 'integer',
        'visitors_count' => 'integer',
    ];

    /**
     * Relacionamento com PGI
     */
    public function pgi()
    {
        return $this->belongsTo(Pgi::class);
    }

    /**
     * Relacionamento com lista de presença
     */
    public function attendances()
    {
        return $this->hasMany(MeetingAttendance::class);
    }

    /**
     * Participantes (membros)
     */
    public function participants()
    {
        return $this->attendances()->where('type', 'participant')->whereNotNull('member_id');
    }

    /**
     * Visitantes
     */
    public function visitors()
    {
        return $this->attendances()->where('type', 'visitor');
    }

    /**
     * Atualiza contadores automaticamente
     */
    public function updateCounters()
    {
        $this->participants_count = $this->attendances()->where('type', 'participant')->whereNotNull('member_id')->count();
        $this->visitors_count = $this->attendances()->where('type', 'visitor')->count();
        $this->save();
    }
}

