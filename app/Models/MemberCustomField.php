<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class MemberCustomField extends Model
{
    public const TYPES = [
        'text' => 'Texto',
        'number' => 'Número',
        'date' => 'Data',
        'select' => 'Lista (select)',
    ];

    protected $fillable = [
        'name',
        'slug',
        'type',
        'options',
        'is_required',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $field) {
            if (blank($field->slug)) {
                $base = Str::slug($field->name) ?: 'campo';
                $slug = $base;
                $i = 1;
                while (static::query()->where('slug', $slug)->exists()) {
                    $slug = $base . '-' . $i++;
                }
                $field->slug = $slug;
            }
        });
    }

    public function values(): HasMany
    {
        return $this->hasMany(MemberCustomFieldValue::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }
}
