<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MemberRole extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relacionamento com Membros
     */
    public function members()
    {
        return $this->hasMany(Member::class, 'role_id');
    }

    /**
     * Scope para cargos ativos
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}


