<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Folder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'parent_id',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Relacionamento com Pasta Pai
     */
    public function parent()
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    /**
     * Relacionamento com Pastas Filhas
     */
    public function children()
    {
        return $this->hasMany(Folder::class, 'parent_id')->orderBy('order');
    }

    /**
     * Relacionamento com Músicas
     */
    public function songs()
    {
        return $this->hasMany(Song::class)->orderBy('order');
    }
}
