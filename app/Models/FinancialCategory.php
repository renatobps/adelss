<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'financial_categories';

    protected $fillable = [
        'name',
        'description',
        'type',
    ];

    /**
     * Scope para filtrar por tipo
     */
    public function scopeReceitas($query)
    {
        return $query->where('type', 'receita');
    }

    public function scopeDespesas($query)
    {
        return $query->where('type', 'despesa');
    }

    /**
     * Acessor para exibir o tipo formatado
     */
    public function getTypeFormattedAttribute()
    {
        return $this->type === 'receita' ? 'Receitas' : 'Despesas';
    }
}
