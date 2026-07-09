<?php

namespace App\Policies\Discipleship;

use App\Policies\Concerns\ModuleResourcePolicy;

class DiscipleshipMeetingPolicy extends ModuleResourcePolicy
{
    protected function resourcePermissionKey(): string
    {
        return 'discipleship.meetings';
    }
}
