<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyFormSubmission extends Model
{
    use HasFactory;

    protected $fillable = [
        'study_form_id',
        'respondent_name',
        'ip_address',
        'score',
        'max_score',
        'submitted_at',
    ];

    protected $casts = [
        'submitted_at' => 'datetime',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(StudyForm::class, 'study_form_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(StudyFormAnswer::class, 'submission_id');
    }
}
