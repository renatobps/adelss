<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudyFormQuestion extends Model
{
    use HasFactory;

    public const TYPE_DISSERTATIVE = 'dissertative';
    public const TYPE_MULTIPLE_CHOICE = 'multiple_choice';
    public const TYPE_TRUE_FALSE = 'true_false';

    protected $fillable = [
        'study_form_id',
        'prompt',
        'theme',
        'type',
        'options',
        'correct_answer',
        'is_required',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
    ];

    public function form(): BelongsTo
    {
        return $this->belongsTo(StudyForm::class, 'study_form_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(StudyFormAnswer::class, 'question_id');
    }

    public function typeLabel(): string
    {
        return match ($this->type) {
            self::TYPE_MULTIPLE_CHOICE => 'Múltipla escolha',
            self::TYPE_TRUE_FALSE => 'Verdadeiro ou falso',
            default => 'Dissertativa',
        };
    }

    public function isAutoGradable(): bool
    {
        return in_array($this->type, [self::TYPE_MULTIPLE_CHOICE, self::TYPE_TRUE_FALSE], true)
            && filled($this->correct_answer);
    }
}
