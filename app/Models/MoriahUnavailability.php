<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MoriahUnavailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'member_id',
        'start_date',
        'end_date',
        'description',
        'is_period',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_period' => 'boolean',
    ];

    /**
     * Relacionamento com Membro
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Verificar se uma data está dentro do período de indisponibilidade
     */
    public function coversDate($date)
    {
        $checkDate = is_string($date) ? \Carbon\Carbon::parse($date) : $date;
        
        if ($this->is_period && $this->end_date) {
            return $checkDate->between($this->start_date, $this->end_date);
        }
        
        return $checkDate->isSameDay($this->start_date);
    }
}
