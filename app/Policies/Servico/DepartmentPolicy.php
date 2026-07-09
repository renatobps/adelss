<?php

namespace App\Policies\Servico;

use App\Policies\Concerns\ModuleResourcePolicy;

class DepartmentPolicy extends ModuleResourcePolicy
{
    protected function resourcePermissionKey(): string
    {
        return 'servico.departments';
    }
}
