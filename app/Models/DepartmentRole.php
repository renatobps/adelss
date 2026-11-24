<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DepartmentRole extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'department_id',
        'name',
        'description',
        'is_default',
        'responsible_member_id',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Relacionamento com Departamento
     */
    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * Relacionamento com membros que têm este cargo
     */
    public function departmentMembers()
    {
        return $this->hasMany(DepartmentMember::class);
    }

    public function responsibleMember()
    {
        return $this->belongsTo(Member::class, 'responsible_member_id');
    }

    public function responsibleMembers()
    {
        return $this->belongsToMany(Member::class, 'department_role_assignments', 'department_role_id', 'member_id')->withTimestamps();
    }

    public function getAssignedMembersAttribute(): \Illuminate\Support\Collection
    {
        return $this->responsibleMembers
            ->concat($this->responsibleMember ? [$this->responsibleMember] : [])
            ->concat($this->departmentMembers->where('department_id', $this->department_id)->pluck('member'))
            ->filter(fn ($member) => $member && !$member->trashed())->unique('id')->values();
    }
}

