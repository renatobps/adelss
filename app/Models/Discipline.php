<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Discipline extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'class_id',
    ];

    /**
     * Relacionamento com Turma
     */
    public function turma()
    {
        return $this->belongsTo(Turma::class, 'class_id');
    }

    /**
     * Relacionamento com Professores (Members)
     */
    public function teachers()
    {
        return $this->belongsToMany(Member::class, 'discipline_teachers', 'discipline_id', 'member_id')
                    ->withTimestamps();
    }

    /**
     * Relacionamento com Aulas
     */
    public function lessons()
    {
        return $this->hasMany(Lesson::class, 'discipline_id');
    }

    /**
     * Relacionamento com Arquivos
     */
    public function files()
    {
        return $this->hasMany(ClassFile::class, 'discipline_id');
    }
}
