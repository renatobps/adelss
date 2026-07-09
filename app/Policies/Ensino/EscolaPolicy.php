<?php

namespace App\Policies\Ensino;

use App\Policies\Concerns\ModuleResourcePolicy;

class EscolaPolicy extends ModuleResourcePolicy
{
    protected function resourcePermissionKey(): string
    {
        return 'ensino.escolas';
    }
}
