<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialContact extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'financial_contacts';

    protected $fillable = [
        'name',
        'email',
        'type',
        'cpf',
        'cnpj',
        'phone_1',
        'phone_2',
        'category_id',
        'notes',
    ];

    /**
     * Relacionamento com categoria
     */
    public function category()
    {
        return $this->belongsTo(FinancialContactCategory::class, 'category_id');
    }

    /**
     * Acessor para exibir o tipo formatado
     */
    public function getTypeFormattedAttribute()
    {
        return $this->type === 'pessoa_fisica' ? 'Pessoa física' : 'Pessoa jurídica';
    }
}
