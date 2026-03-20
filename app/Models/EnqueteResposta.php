<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnqueteResposta extends Model
{
    protected $fillable = [
        'enquete_id',
        'member_id',
        'telefone',
        'resposta',
        'respondido_em',
    ];

    protected $casts = [
        'respondido_em' => 'datetime',
    ];

    public function enquete(): BelongsTo
    {
        return $this->belongsTo(Enquete::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }
}
