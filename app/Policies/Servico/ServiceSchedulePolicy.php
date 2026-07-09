<?php

namespace App\Policies\Servico;

use App\Policies\Concerns\ModuleResourcePolicy;

class ServiceSchedulePolicy extends ModuleResourcePolicy
{
    protected function resourcePermissionKey(): string
    {
        return 'servico.voluntarios.escalas';
    }
}
