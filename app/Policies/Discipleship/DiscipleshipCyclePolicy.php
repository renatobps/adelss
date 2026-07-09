<?php

namespace App\Policies\Discipleship;

use App\Policies\Concerns\ModuleResourcePolicy;

class DiscipleshipCyclePolicy extends ModuleResourcePolicy
{
    protected function resourcePermissionKey(): string
    {
        return 'discipleship.cycles';
    }
}
