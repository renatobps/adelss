<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pgi extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'logo_url',
        'banner_url',
        'opening_date',
        'day_of_week',
        'profile',
        'time_schedule',
        'leader_1_id',
        'leader_2_id',
        'leader_training_1_id',
        'leader_training_2_id',
        'address',
        'neighborhood',
        'number',
        'notes',
    ];

    protected $casts = [
        'opening_date' => 'date',
    ];

    /**
     * Relacionamento com Líder 1
     */
    public function leader1()
    {
        return $this->belongsTo(Member::class, 'leader_1_id');
    }

    /**
     * Relacionamento com Líder 2
     */
    public function leader2()
    {
        return $this->belongsTo(Member::class, 'leader_2_id');
    }

    /**
     * Relacionamento com Líder em Treinamento 1
     */
    public function leaderTraining1()
    {
        return $this->belongsTo(Member::class, 'leader_training_1_id');
    }

    /**
     * Relacionamento com Líder em Treinamento 2
     */
    public function leaderTraining2()
    {
        return $this->belongsTo(Member::class, 'leader_training_2_id');
    }

    /**
     * Relacionamento com membros (membros que pertencem a este PGI)
     */
    public function members()
    {
        return $this->hasMany(Member::class);
    }

    /**
     * Retorna todos os líderes (1, 2 e em treinamento)
     */
    public function getAllLeaders()
    {
        $leaders = collect();
        
        if ($this->leader1) $leaders->push($this->leader1);
        if ($this->leader2) $leaders->push($this->leader2);
        if ($this->leaderTraining1) $leaders->push($this->leaderTraining1);
        if ($this->leaderTraining2) $leaders->push($this->leaderTraining2);
        
        return $leaders;
    }

    /**
     * Relacionamento com Reuniões
     */
    public function meetings()
    {
        return $this->hasMany(Meeting::class);
    }
}

