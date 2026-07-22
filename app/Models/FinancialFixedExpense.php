<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialFixedExpense extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'description',
        'amount',
        'due_day',
        'category_id',
        'account_id',
        'cost_center_id',
        'contact_id',
        'notes',
        'is_active',
        'amount_variable',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'due_day' => 'integer',
        'is_active' => 'boolean',
        'amount_variable' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(FinancialCategory::class, 'category_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(FinancialAccount::class, 'account_id');
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(FinancialCostCenter::class, 'cost_center_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(FinancialContact::class, 'contact_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class, 'fixed_expense_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function dueDateFor(Carbon $month): Carbon
    {
        $day = min(max(1, (int) $this->due_day), $month->daysInMonth);

        return $month->copy()->startOfMonth()->day($day)->startOfDay();
    }

    public function transactionForMonth(Carbon $month): ?FinancialTransaction
    {
        return $this->transactions()
            ->whereYear('competence_date', $month->year)
            ->whereMonth('competence_date', $month->month)
            ->first();
    }

    public function isGeneratedForMonth(Carbon $month): bool
    {
        return $this->transactions()
            ->whereYear('competence_date', $month->year)
            ->whereMonth('competence_date', $month->month)
            ->exists();
    }
}
