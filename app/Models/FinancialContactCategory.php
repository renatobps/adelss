<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialContactCategory extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'financial_contact_categories';

    protected $fillable = [
        'name',
    ];

    /**
     * Relacionamento com contatos
     */
    public function contacts()
    {
        return $this->hasMany(FinancialContact::class, 'category_id');
    }
}
