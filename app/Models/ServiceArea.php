<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ServiceArea extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'status',
        'leader_id',
        'min_quantity',
        'allowed_audience',
    ];

    protected $casts = [
        'status' => 'string',
        'allowed_audience' => 'string',
        'min_quantity' => 'integer',
    ];

    /**
     * Relacionamento com Líder (Responsável)
     */
    public function leader()
    {
        return $this->belongsTo(Member::class, 'leader_id');
    }

    /**
     * Relacionamento muitos-para-muitos com Voluntários
     */
    public function volunteers()
    {
        return $this->belongsToMany(Volunteer::class, 'volunteer_service_areas', 'service_area_id', 'volunteer_id')
                    ->withTimestamps();
    }

    /**
     * Scope para áreas ativas
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ativo');
    }
}
