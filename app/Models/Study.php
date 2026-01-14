<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Study extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'studies';

    protected $fillable = [
        'name',
        'category',
        'content',
        'featured_image',
        'attachment',
        'attachment_name',
        'send_notification',
    ];

    protected $casts = [
        'send_notification' => 'boolean',
    ];

    /**
     * Scope para filtrar por categoria
     */
    public function scopeByCategory($query, $category)
    {
        if ($category) {
            return $query->where('category', $category);
        }
        return $query;
    }

    /**
     * Scope para busca por nome
     */
    public function scopeSearch($query, $search)
    {
        if ($search) {
            return $query->where('name', 'like', "%{$search}%");
        }
        return $query;
    }
}
