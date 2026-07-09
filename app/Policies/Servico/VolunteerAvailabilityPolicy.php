<?php

namespace App\Policies\Servico;

use App\Policies\Concerns\ModuleResourcePolicy;

class VolunteerAvailabilityPolicy extends ModuleResourcePolicy
{
    protected function resourcePermissionKey(): string
    {
        return 'servico.voluntarios.disponibilidade';
    }
}
