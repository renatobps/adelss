<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rifa extends Model
{
    use HasFactory;

    protected $fillable = [
        'nome',
        'quantidade_numeros',
        'valor_numero',
        'numeros_por_cartela',
        'data_sorteio',
        'status',
        'department_id',
        'pix_chave',
        'pix_recebedor',
    ];

    protected $casts = [
        'valor_numero' => 'decimal:2',
        'data_sorteio' => 'date',
    ];

    public function numeros(): HasMany
    {
        return $this->hasMany(NumeroRifa::class);
    }

    public function department(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function vendedoresSelecionados(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Member::class, 'rifa_vendedores', 'rifa_id', 'member_id')->withTimestamps();
    }

    public function temDistribuicaoAutomatica(): bool
    {
        return (bool) $this->department_id || $this->vendedoresSelecionados()->exists();
    }

    public function membrosVendedores(bool $incluirDistribuidos = true): \Illuminate\Database\Eloquent\Builder
    {
        $query = Member::query();
        if ($this->temDistribuicaoAutomatica()) {
            $query->where('status', Member::STATUS_ATIVO);
            $departmentId = $this->department_id;
            $query->where(function ($q) use ($departmentId, $incluirDistribuidos) {
                $q->whereIn('members.id', $this->vendedoresSelecionados()->select('members.id'));
                if ($departmentId) {
                    $q->orWhere('department_id', $departmentId)
                        ->orWhereHas('departments', fn ($departments) => $departments->where('departments.id', $departmentId));
                }
                // Removing someone from future distribution never transfers their old cards.
                if ($incluirDistribuidos) {
                    $q->orWhereIn('members.id', $this->cartelas()->whereNotNull('vendedor_id')->select('vendedor_id'));
                }
            });
        }
        return $query->orderBy('name')->orderBy('id');
    }

    public function cartelas(): HasMany
    {
        return $this->hasMany(Cartela::class);
    }

    public function vendas(): HasMany
    {
        return $this->hasMany(RifaVenda::class);
    }

    public function sorteios(): HasMany
    {
        return $this->hasMany(SorteioRifa::class);
    }
}
