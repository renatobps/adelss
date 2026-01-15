<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'start_date',
        'end_date',
        'all_day',
        'recurrence',
        'visibility',
        'status',
        'location',
        'category_id',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'all_day' => 'boolean',
    ];

    /**
     * Relacionamento com Categoria
     */
    public function category()
    {
        return $this->belongsTo(EventCategory::class, 'category_id');
    }
}

