<?php

namespace App\Models;

use App\Support\Cp437Utf8MojibakeFixer;
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
        'show_on_homepage',
        'homepage_order',
        'homepage_url',
    ];

    protected $casts = [
        'status' => 'string',
        'show_on_homepage' => 'boolean',
        'homepage_order' => 'integer',
    ];

    public function getNameAttribute(?string $value): string
    {
        return $this->repairCp437Utf8((string) $value);
    }

    public function setNameAttribute(?string $value): void
    {
        $this->attributes['name'] = $this->repairCp437Utf8((string) $value);
    }

    public function getDescriptionAttribute(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return $this->repairCp437Utf8($value);
    }

    public function setDescriptionAttribute(?string $value): void
    {
        $this->attributes['description'] = $value === null || $value === ''
            ? $value
            : $this->repairCp437Utf8($value);
    }

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

    private function repairCp437Utf8(string $value): string
    {
        $repaired = (new Cp437Utf8MojibakeFixer)->repair($value);
        $repaired = preg_replace('/\x{00AD}\x{0192}[\x{00A0}-\x{00FF}]{1,2}$/u', '', $repaired) ?? $repaired;

        return rtrim($repaired);
    }
}

