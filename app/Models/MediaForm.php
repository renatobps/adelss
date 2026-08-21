<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class MediaForm extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'description',
        'public_slug',
        'is_active',
        'is_accepting_responses',
        'requires_identification',
        'success_message',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_accepting_responses' => 'boolean',
        'requires_identification' => 'boolean',
    ];

    public function fields()
    {
        return $this->hasMany(MediaFormField::class)->orderBy('sort_order')->orderBy('id');
    }

    public function submissions()
    {
        return $this->hasMany(MediaFormSubmission::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeOpen($query)
    {
        return $query->where('is_active', true)->where('is_accepting_responses', true);
    }

    public function publicUrl(): string
    {
        return route('formularios.public.show', $this->public_slug);
    }

    public function isOpen(): bool
    {
        return $this->is_active && $this->is_accepting_responses;
    }

    public function statusLabel(): string
    {
        if (!$this->is_active) {
            return 'Inativo';
        }

        return $this->is_accepting_responses ? 'Recebendo respostas' : 'Respostas encerradas';
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
