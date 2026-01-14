<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class DepartmentMember extends Pivot
{
    protected $table = 'department_members';

    protected $fillable = [
        'department_id',
        'member_id',
        'department_role_id',
    ];

    /**
     * Indica se o modelo deve usar timestamps
     */
    public $timestamps = true;

    /**
     * Indica se o modelo tem ID autoincrementável
     */
    public $incrementing = true;

    /**
     * Os atributos que devem ser convertidos para tipos nativos.
     */
    protected $casts = [
        'id' => 'integer',
        'department_id' => 'integer',
        'member_id' => 'integer',
        'department_role_id' => 'integer',
    ];

    /**
     * Relacionamento com Departamento
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Relacionamento com Membro
     */
    public function member()
    {
        return $this->belongsTo(Member::class);
    }

    /**
     * Relacionamento com Cargo/Função
     */
    public function role()
    {
        return $this->belongsTo(DepartmentRole::class, 'department_role_id');
    }
}
