<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'culto_id',
        'account_id',
        'cost_center_id',
        'payment_type',
        'installments_count',
        'installment_number',
        'parent_transaction_id',
        'fixed_expense_id',
        'document_number',
        'external_ref',
        'notes',
        'competence_date',
        'created_by',
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

    public function culto()
    {
        return $this->belongsTo(Event::class, 'culto_id');
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

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class, 'financial_transaction_id');
    }

    public function latestPaymentTransaction(): HasOne
    {
        return $this->hasOne(PaymentTransaction::class, 'financial_transaction_id')->latestOfMany();
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function parentTransaction()
    {
        return $this->belongsTo(self::class, 'parent_transaction_id');
    }

    public function fixedExpense()
    {
        return $this->belongsTo(FinancialFixedExpense::class, 'fixed_expense_id');
    }

    public function childInstallments(): HasMany
    {
        return $this->hasMany(self::class, 'parent_transaction_id');
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(FinancialNotificationLog::class, 'financial_transaction_id');
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

    public static function displayDescription(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $clean = preg_replace('/^\[Importação(?:\s+\d{4})?\]\s*/iu', '', $value);

        return trim($clean ?? $value);
    }

    public function getDescriptionAttribute(?string $value): string
    {
        return self::displayDescription($value);
    }

    /**
     * Acessor para exibir o valor formatado
     */
    public function getFormattedAmountAttribute()
    {
        $sign = $this->type === 'despesa' ? '-' : '';

        return $sign.'R$ '.number_format($this->amount, 2, ',', '.');
    }

    /**
     * Acessor para obter o nome de quem deu/recebeu
     */
    public function getSourceNameAttribute()
    {
        if ($this->member_id) {
            return $this->member->name ?? 'Membro não encontrado';
        }

        if (filled($this->received_from_other)) {
            return $this->received_from_other;
        }

        if ($this->type === 'despesa') {
            return $this->contact->name ?? '—';
        }

        return 'Outros';
    }

    /**
     * Nome na listagem: dizimista na receita, favorecido na despesa (vazio se não houver).
     */
    public function listingPersonName(): string
    {
        if ($this->member?->name) {
            return $this->member->name;
        }

        if (filled($this->received_from_other)) {
            return (string) $this->received_from_other;
        }

        if ($this->type === 'despesa' && $this->contact?->name) {
            return $this->contact->name;
        }

        return '';
    }
}
