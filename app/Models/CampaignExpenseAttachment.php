<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Comprovante (nota, recibo, foto) de um gasto de evento, salvo no disco public. */
class CampaignExpenseAttachment extends Model
{
    public const STORAGE_DIR = 'eventos/campanhas/gastos';

    protected $fillable = [
        'campaign_expense_id',
        'file_path',
        'file_name',
        'file_type',
        'file_size',
    ];

    public function expense(): BelongsTo
    {
        return $this->belongsTo(CampaignExpense::class, 'campaign_expense_id');
    }

    public function isImage(): bool
    {
        return str_starts_with((string) $this->file_type, 'image/');
    }

    public function humanSize(): string
    {
        $bytes = (int) $this->file_size;
        if ($bytes <= 0) {
            return '—';
        }
        if ($bytes < 1024 * 1024) {
            return number_format($bytes / 1024, 0, ',', '.') . ' KB';
        }

        return number_format($bytes / (1024 * 1024), 1, ',', '.') . ' MB';
    }
}
