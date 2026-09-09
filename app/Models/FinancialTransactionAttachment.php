<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialTransactionAttachment extends Model
{
    use HasFactory;

    /** Pasta dos recibos emitidos pelo próprio sistema, no disco public. */
    public const GENERATED_DIR = 'financial/transactions/generated-receipts';

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

    /**
     * Recibo emitido pelo sistema, e não enviado pelo usuário.
     */
    public function isSystemGenerated(): bool
    {
        return str_starts_with((string) $this->file_path, self::GENERATED_DIR.'/');
    }
}
