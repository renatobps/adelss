<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class StudyForm extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'study_id',
        'title',
        'description',
        'public_slug',
        'is_active',
        'import_key',
        'material_path',
        'exam_pdf_path',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function study(): BelongsTo
    {
        return $this->belongsTo(Study::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(StudyFormQuestion::class)->orderBy('sort_order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(StudyFormSubmission::class);
    }

    public function publicUrl(): string
    {
        return url('/estudo-formulario/'.$this->public_slug);
    }

    public function materialUrl(): ?string
    {
        return $this->material_path ? asset('storage/'.$this->material_path) : null;
    }

    public function examPdfUrl(): ?string
    {
        return $this->exam_pdf_path ? asset('storage/'.$this->exam_pdf_path) : null;
    }

    public static function generateUniqueSlug(string $title): string
    {
        $base = Str::slug($title);
        if ($base === '') {
            $base = 'formulario';
        }

        $slug = $base.'-'.Str::lower(Str::random(4));
        while (static::withTrashed()->where('public_slug', $slug)->exists()) {
            $slug = $base.'-'.Str::lower(Str::random(4));
        }

        return $slug;
    }
}
