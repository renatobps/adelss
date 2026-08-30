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
        'slug',
        'description',
        'type',
        'sends_receipt',
    ];

    protected $casts = [
        'sends_receipt' => 'boolean',
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

    public function isDizimo(): bool
    {
        return self::slugIsDizimo($this->slug, $this->name);
    }

    public function isDizimoOuOferta(): bool
    {
        return self::slugIsDizimoOuOferta($this->slug, $this->name);
    }

    public static function slugIsDizimo(?string $slug, ?string $name = null): bool
    {
        $slug = strtolower(trim((string) $slug));
        if ($slug === 'dizimo') {
            return true;
        }

        $name = mb_strtolower((string) $name);

        return str_contains($name, 'dízimo') || str_contains($name, 'dizimo');
    }

    public static function slugIsDizimoOuOferta(?string $slug, ?string $name = null): bool
    {
        $slug = strtolower(trim((string) $slug));
        if (in_array($slug, ['dizimo', 'oferta'], true)) {
            return true;
        }

        $name = mb_strtolower((string) $name);

        return str_contains($name, 'dízimo')
            || str_contains($name, 'dizimo')
            || str_contains($name, 'oferta');
    }

    /**
     * Acessor para exibir o tipo formatado
     */
    public function getTypeFormattedAttribute()
    {
        return $this->type === 'receita' ? 'Receitas' : 'Despesas';
    }
}
