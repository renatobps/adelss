<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MoriahFunction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'icon',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Relacionamento com Membros (muitos para muitos)
     */
    public function members()
    {
        return $this->belongsToMany(Member::class, 'member_moriah_functions', 'moriah_function_id', 'member_id')
                    ->withTimestamps();
    }
}
