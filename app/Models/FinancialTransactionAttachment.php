<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialTransactionAttachment extends Model
{
    use HasFactory;

    protected $table = 'financial_transaction_attachments';

    protected $fillable = [
        'transaction_id',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
    ];

    /**
     * Relacionamento com transação
     */
    public function transaction()
    {
        return $this->belongsTo(FinancialTransaction::class, 'transaction_id');
    }
}
