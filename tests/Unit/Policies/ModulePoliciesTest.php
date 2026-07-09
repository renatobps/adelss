<?php

namespace Tests\Unit\Policies;

use App\Models\User;
use App\Policies\Notificacoes\NotificacaoPolicy;
use App\Policies\Servico\DepartmentPolicy;
use Tests\TestCase;

class ModulePoliciesTest extends TestCase
{
    public function test_usuario_com_permissao_pode_ver_departamentos(): void
    {
        $policy = new DepartmentPolicy();
        $user = $this->userWithPermissions(['servico.departments.view']);

        $this->assertTrue($policy->viewAny($user));
    }

    public function test_usuario_sem_permissao_nao_gerencia_notificacoes(): void
    {
        $policy = new NotificacaoPolicy();
        $user = $this->userWithPermissions(['notificacoes.view']);

        $this->assertTrue($policy->viewAny($user));
        $this->assertFalse($policy->manage($user));
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithPermissions(array $permissions): User
    {
        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['hasPermission'])
            ->getMock();

        $user->is_admin = false;
        $user->method('hasPermission')->willReturnCallback(
            static fn (string $key): bool => in_array($key, $permissions, true)
        );

        return $user;
    }
}
