<?php

namespace App\Policies\Discipleship;

use App\Policies\Concerns\ModuleResourcePolicy;

class DiscipleshipIndicatorPolicy extends ModuleResourcePolicy
{
    protected function resourcePermissionKey(): string
    {
        return 'discipleship.indicators';
    }
}
