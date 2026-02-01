<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MoriahSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'event_id',
        'title',
        'date',
        'time',
        'observations',
        'status',
        'request_confirmation',
    ];

    protected $casts = [
        'date' => 'date',
        'time' => 'datetime',
        'request_confirmation' => 'boolean',
    ];

    /**
     * Relacionamento com Evento (Culto)
     */
    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Relacionamento com Membros (Participantes)
     */
    public function members()
    {
        return $this->belongsToMany(Member::class, 'moriah_schedule_members', 'moriah_schedule_id', 'member_id')
                    ->withPivot('status')
                    ->withTimestamps();
    }

    /**
     * Relacionamento com Músicas
     */
    public function songs()
    {
        return $this->belongsToMany(Song::class, 'moriah_schedule_songs', 'moriah_schedule_id', 'song_id')
                    ->withPivot('order')
                    ->orderBy('moriah_schedule_songs.order')
                    ->withTimestamps();
    }
}
