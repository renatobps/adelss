<?php

namespace App\Policies\Discipleship;

use App\Policies\Concerns\ModuleResourcePolicy;

class DiscipleshipFeedbackPolicy extends ModuleResourcePolicy
{
    protected function resourcePermissionKey(): string
    {
        return 'discipleship.feedbacks';
    }
}
