<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'manager_id',
    ];

    /**
     * Relacionamento com Gestor (Membro)
     */
    public function manager()
    {
        return $this->belongsTo(Member::class, 'manager_id');
    }

    /**
     * Relacionamento com Turmas (Classes)
     */
    public function turmas()
    {
        return $this->hasMany(\App\Models\Turma::class, 'school_id');
    }

    /**
     * Scope para busca por nome
     */
    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where('name', 'like', "%{$search}%");
        }
        return $query;
    }
}
