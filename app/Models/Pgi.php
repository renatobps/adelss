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
        'parent_pgi_id',
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
        'latitude',
        'longitude',
        'geocoded_at',
        'notes',
    ];

    protected $casts = [
        'opening_date' => 'date',
        'latitude' => 'float',
        'longitude' => 'float',
        'geocoded_at' => 'datetime',
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
     * Verifica se um membro é líder ou líder em treinamento deste PGI
     */
    public function isLeader(Member $member): bool
    {
        return $this->leader_1_id == $member->id ||
               $this->leader_2_id == $member->id ||
               $this->leader_training_1_id == $member->id ||
               $this->leader_training_2_id == $member->id;
    }

    /**
     * Relacionamento com Reuniões
     */
    public function meetings()
    {
        return $this->hasMany(Meeting::class);
    }

    /**
     * PGI que gerou este grupo (multiplicação)
     */
    public function parent()
    {
        return $this->belongsTo(Pgi::class, 'parent_pgi_id');
    }

    /**
     * PGIs gerados a partir deste grupo
     */
    public function children()
    {
        return $this->hasMany(Pgi::class, 'parent_pgi_id');
    }

    /**
     * Endereço em uma linha, para exibição e geocodificação.
     */
    public function fullAddress(): string
    {
        $parts = array_filter([
            trim((string) $this->address),
            trim((string) $this->number),
            trim((string) $this->neighborhood),
        ], fn ($part) => $part !== '');

        return implode(', ', $parts);
    }

    public function hasCoordinates(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * Dia da semana do PGI no formato do Carbon (0 = domingo).
     */
    public function dayOfWeekNumber(): ?int
    {
        $map = [
            'domingo' => 0,
            'segunda' => 1,
            'terça' => 2,
            'quarta' => 3,
            'quinta' => 4,
            'sexta' => 5,
            'sábado' => 6,
        ];

        return $map[mb_strtolower(trim((string) $this->day_of_week))] ?? null;
    }
}

