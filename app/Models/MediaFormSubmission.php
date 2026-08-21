<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MediaFormSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'media_form_id',
        'respondent_name',
        'respondent_phone',
        'ip_address',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function form()
    {
        return $this->belongsTo(MediaForm::class, 'media_form_id');
    }

    public function answers()
    {
        return $this->hasMany(MediaFormAnswer::class, 'submission_id');
    }

    public function respondentLabel(): string
    {
        return filled($this->respondent_name) ? $this->respondent_name : 'Sem identificacao';
    }

    /** Respostas indexadas por campo, para montar linhas de tabela e exportacoes. */
    public function answersByField(): array
    {
        return $this->answers->keyBy('field_id')->all();
    }
}
