<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'template',
        'icon',
        'color',
        'status',
        'description',
        'leader_id',
        'banner_url',
        'logo_url',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    /**
     * Relacionamento com o líder (membro) - mantido para compatibilidade
     * @deprecated Use leaders() para múltiplos líderes
     */
    public function leader()
    {
        return $this->belongsTo(Member::class, 'leader_id');
    }

    /**
     * Relacionamento com múltiplos líderes (muitos para muitos)
     */
    public function leaders()
    {
        return $this->belongsToMany(Member::class, 'department_leaders', 'department_id', 'member_id')
                    ->withTimestamps();
    }

    /**
     * Obtém os registros pivot (department_members) diretamente
     * Note: Não é um relacionamento Eloquent, mas uma query builder
     */
    public function getDepartmentMembersQuery()
    {
        return DepartmentMember::where('department_id', $this->id);
    }

    /**
     * Relacionamento com membros (muitos para muitos)
     */
    public function members()
    {
        return $this->belongsToMany(Member::class, 'department_members', 'department_id', 'member_id')
                    ->withPivot('id', 'department_role_id', 'created_at', 'updated_at')
                    ->withTimestamps();
    }

    /**
     * Relacionamento com cargos/funções
     */
    public function roles()
    {
        return $this->hasMany(DepartmentRole::class);
    }

    /**
     * Scope para departamentos ativos
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'ativo');
    }

    /**
     * Scope para departamentos arquivados
     */
    public function scopeArchived($query)
    {
        return $query->where('status', 'arquivado');
    }

    /**
     * Relacionamento many-to-many com centros de custo
     */
    public function costCenters()
    {
        return $this->belongsToMany(FinancialCostCenter::class, 'cost_center_departments', 'department_id', 'cost_center_id')
                    ->withTimestamps();
    }
}

