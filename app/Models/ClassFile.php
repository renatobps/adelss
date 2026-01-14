<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClassFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'class_id',
        'discipline_id',
        'title',
        'type',
        'file_path',
        'content',
        'external_url',
        'description',
    ];

    protected $casts = [
        'type' => 'string',
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
}
