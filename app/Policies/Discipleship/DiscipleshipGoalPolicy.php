<?php

namespace App\Policies\Discipleship;

use App\Policies\Concerns\ModuleResourcePolicy;

class DiscipleshipGoalPolicy extends ModuleResourcePolicy
{
    protected function resourcePermissionKey(): string
    {
        return 'discipleship.goals';
    }
}
