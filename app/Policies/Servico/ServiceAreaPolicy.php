<?php

namespace App\Policies\Servico;

use App\Policies\Concerns\ModuleResourcePolicy;

class ServiceAreaPolicy extends ModuleResourcePolicy
{
    protected function resourcePermissionKey(): string
    {
        return 'servico.voluntarios.areas';
    }
}
