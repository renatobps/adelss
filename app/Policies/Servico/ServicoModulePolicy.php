<?php

namespace App\Policies\Servico;

use App\Models\User;

class ServicoModulePolicy
{
    public function accessAny(User $user): bool
    {
        if ($user->is_admin) {
            return true;
        }

        $viewPermissions = [
            'servico.departments.view',
            'servico.voluntarios.cadastro.view',
            'servico.voluntarios.areas.view',
            'servico.voluntarios.disponibilidade.view',
            'servico.voluntarios.escalas.view',
            'servico.voluntarios.historico.view',
            'servico.voluntarios.relatorios.view',
            'servico.departments.manage',
            'servico.voluntarios.cadastro.manage',
            'servico.voluntarios.areas.manage',
            'servico.voluntarios.disponibilidade.manage',
            'servico.voluntarios.escalas.manage',
            'servico.voluntarios.historico.manage',
            'servico.voluntarios.relatorios.manage',
        ];

        foreach ($viewPermissions as $permission) {
            if ($user->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }
}
