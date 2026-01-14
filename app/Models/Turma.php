<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Turma extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'classes';

    protected $fillable = [
        'name',
        'school_id',
        'schedule',
        'status',
        'description',
    ];

    protected $casts = [
        'schedule' => 'string',
        'status' => 'string',
    ];

    /**
     * Relacionamento com Escola
     */
    public function school()
    {
        return $this->belongsTo(School::class, 'school_id');
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

    /**
     * Scope para filtrar por status
     */
    public function scopeByStatus($query, $status)
    {
        if ($status) {
            return $query->where('status', $status);
        }
        return $query;
    }

    /**
     * Scope para filtrar por escola
     */
    public function scopeBySchool($query, $schoolId)
    {
        if ($schoolId) {
            return $query->where('school_id', $schoolId);
        }
        return $query;
    }

    /**
     * Relacionamento com Alunos (Members)
     */
    public function students()
    {
        return $this->belongsToMany(Member::class, 'class_students', 'class_id', 'member_id')
                    ->withTimestamps();
    }

    /**
     * Relacionamento com Disciplinas
     */
    public function disciplines()
    {
        return $this->hasMany(Discipline::class, 'class_id');
    }

    /**
     * Relacionamento com Aulas
     */
    public function lessons()
    {
        return $this->hasMany(Lesson::class, 'class_id');
    }

    /**
     * Relacionamento com Arquivos
     */
    public function files()
    {
        return $this->hasMany(ClassFile::class, 'class_id');
    }
}
