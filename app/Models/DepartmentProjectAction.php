<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DepartmentProjectAction extends Model
{
    protected $fillable = ['name', 'details'];

    public function project(): BelongsTo
    {
        return $this->belongsTo(DepartmentProject::class, 'department_project_id');
    }
}
