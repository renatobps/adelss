<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinancialCostCenter extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'financial_cost_centers';

    protected $fillable = [
        'name',
        'description',
    ];

    /**
     * Relacionamento many-to-many com departamentos
     */
    public function departments()
    {
        return $this->belongsToMany(Department::class, 'cost_center_departments', 'cost_center_id', 'department_id')
                    ->withTimestamps();
    }
}
