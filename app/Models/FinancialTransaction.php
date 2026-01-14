<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialTransaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'financial_transactions';

    protected $fillable = [
        'type',
        'transaction_date',
        'description',
        'amount',
        'is_paid',
        'due_date',
        'status',
        'member_id',
        'received_from_other',
        'contact_id',
        'category_id',
        'account_id',
        'cost_center_id',
        'payment_type',
        'document_number',
        'notes',
        'competence_date',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'due_date' => 'date',
        'competence_date' => 'date',
        'amount' => 'decimal:2',
        'is_paid' => 'boolean',
    ];

    /**
     * Relacionamento com membro (para receitas)
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Relacionamento com contato (para despesas)
     */
    public function contact()
    {
        return $this->belongsTo(FinancialContact::class, 'contact_id');
    }

    /**
     * Relacionamento com categoria
     */
    public function category()
    {
        return $this->belongsTo(FinancialCategory::class, 'category_id');
    }

    /**
     * Relacionamento com conta
     */
    public function account()
    {
        return $this->belongsTo(FinancialAccount::class, 'account_id');
    }

    /**
     * Relacionamento com centro de custo
     */
    public function costCenter()
    {
        return $this->belongsTo(FinancialCostCenter::class, 'cost_center_id');
    }

    /**
     * Relacionamento com anexos
     */
    public function attachments()
    {
        return $this->hasMany(FinancialTransactionAttachment::class, 'transaction_id');
    }

    /**
     * Scope para receitas
     */
    public function scopeReceitas($query)
    {
        return $query->where('type', 'receita');
    }

    /**
     * Scope para despesas
     */
    public function scopeDespesas($query)
    {
        return $query->where('type', 'despesa');
    }

    /**
     * Acessor para exibir o valor formatado
     */
    public function getFormattedAmountAttribute()
    {
        $sign = $this->type === 'despesa' ? '-' : '';
        return $sign . 'R$ ' . number_format($this->amount, 2, ',', '.');
    }

    /**
     * Acessor para obter o nome de quem deu/recebeu
     */
    public function getSourceNameAttribute()
    {
        if ($this->type === 'receita') {
            if ($this->member_id) {
                return $this->member->name ?? 'Membro não encontrado';
            }
            return $this->received_from_other ?? 'Outros';
        } else {
            return $this->contact->name ?? 'Contato não encontrado';
        }
    }
}
