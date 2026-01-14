<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'class_id',
        'discipline_id',
        'lesson_date',
        'subject',
        'notes',
    ];

    protected $casts = [
        'lesson_date' => 'date',
    ];

    /**
     * Relacionamento com Turma
     */
    public function turma()
    {
        return $this->belongsTo(Turma::class, 'class_id');
    }

    /**
     * Relacionamento com Disciplina
     */
    public function discipline()
    {
        return $this->belongsTo(Discipline::class, 'discipline_id');
    }

    /**
     * Relacionamento com Presenças
     */
    public function attendances()
    {
        return $this->hasMany(LessonAttendance::class, 'lesson_id');
    }

    /**
     * Relacionamento com Alunos Presentes (Members)
     */
    public function presentStudents()
    {
        return $this->belongsToMany(Member::class, 'lesson_attendances', 'lesson_id', 'member_id')
                    ->wherePivot('present', true)
                    ->withPivot('notes')
                    ->withTimestamps();
    }
}
