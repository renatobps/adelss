<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'lesson_id',
        'member_id',
        'present',
        'notes',
    ];

    protected $casts = [
        'present' => 'boolean',
    ];

    /**
     * Relacionamento com Aula
     */
    public function lesson()
    {
        return $this->belongsTo(Lesson::class, 'lesson_id');
    }

    /**
     * Relacionamento com Membro
     */
    public function member()
    {
        return $this->belongsTo(Member::class, 'member_id');
    }
}
