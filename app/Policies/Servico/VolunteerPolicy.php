<?php

namespace App\Policies\Servico;

use App\Policies\Concerns\ModuleResourcePolicy;

class VolunteerPolicy extends ModuleResourcePolicy
{
    protected function resourcePermissionKey(): string
    {
        return 'servico.voluntarios.cadastro';
    }
}
