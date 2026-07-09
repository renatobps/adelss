<?php

namespace Tests\Unit\Policies\Members;

use App\Models\Member;
use App\Models\User;
use App\Policies\Members\MemberPolicy;
use Tests\TestCase;

class MemberPolicyTest extends TestCase
{
    private MemberPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new MemberPolicy();
    }

    public function test_usuario_pode_ver_proprio_perfil_sem_permissao_de_listagem(): void
    {
        $member = new Member(['id' => 42]);
        $user = $this->userWithMember(42, []);

        $this->assertTrue($this->policy->view($user, $member));
        $this->assertFalse($this->policy->viewAny($user));
    }

    public function test_usuario_pode_editar_proprio_perfil_sem_permissao_de_edicao(): void
    {
        $member = new Member(['id' => 7]);
        $user = $this->userWithMember(7, []);

        $this->assertTrue($this->policy->update($user, $member));
    }

    public function test_usuario_nao_pode_excluir_proprio_perfil_sem_permissao(): void
    {
        $member = new Member(['id' => 7]);
        $user = $this->userWithMember(7, []);

        $this->assertFalse($this->policy->delete($user, $member));
    }

    public function test_usuario_com_permissao_pode_listar_membros(): void
    {
        $user = $this->userWithMember(null, ['members.index.view']);

        $this->assertTrue($this->policy->viewAny($user));
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithMember(?int $memberId, array $permissions): User
    {
        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['hasPermission'])
            ->getMock();

        $user->is_admin = false;
        $user->method('hasPermission')->willReturnCallback(
            static fn (string $key): bool => in_array($key, $permissions, true)
        );

        if ($memberId !== null) {
            $member = new Member(['id' => $memberId]);
            $user->setRelation('member', $member);
        }

        return $user;
    }
}
