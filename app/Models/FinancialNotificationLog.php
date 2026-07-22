<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialNotificationLog extends Model
{
    public const TYPE_RECEIPT_MEMBER = 'receipt_member';
    public const TYPE_EXPENSE_TREASURER = 'expense_treasurer';
    public const TYPE_EXPENSE_DUE_REMINDER = 'expense_due_reminder';
    public const TYPE_EXPENSE_DUE_REMINDER_SECOND = 'expense_due_reminder_second';
    public const TYPE_SMART_SUMMARY = 'smart_summary';

    protected $fillable = [
        'financial_transaction_id',
        'member_id',
        'phone',
        'notification_type',
        'status',
        'message',
        'error',
        'triggered_by_user_id',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(FinancialTransaction::class, 'financial_transaction_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by_user_id');
    }
}
