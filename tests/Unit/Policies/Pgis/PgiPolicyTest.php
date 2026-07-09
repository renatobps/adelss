<?php

namespace Tests\Unit\Policies\Pgis;

use App\Models\Member;
use App\Models\Pgi;
use App\Models\User;
use App\Policies\Pgis\PgiPolicy;
use Tests\TestCase;

class PgiPolicyTest extends TestCase
{
    private PgiPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new PgiPolicy();
    }

    public function test_membro_do_pgi_pode_ver_proprio_pgi_sem_permissao_global(): void
    {
        $pgi = new Pgi();
        $pgi->id = 5;
        $user = $this->userWithMember($this->makeMember(10, 5), []);

        $this->assertTrue($this->policy->view($user, $pgi));
        $this->assertTrue($this->policy->viewAny($user));
    }

    public function test_lider_pode_gerenciar_reunioes_sem_permissao_global(): void
    {
        $pgi = new Pgi(['leader_1_id' => 8]);
        $pgi->id = 3;
        $user = $this->userWithMember($this->makeMember(8), []);

        $this->assertTrue($this->policy->manageMeetings($user, $pgi));
        $this->assertTrue($this->policy->sendNotification($user, $pgi));
    }

    public function test_membro_comum_nao_pode_gerenciar_reunioes(): void
    {
        $pgi = new Pgi(['leader_1_id' => 99]);
        $pgi->id = 3;
        $user = $this->userWithMember($this->makeMember(8, 3), []);

        $this->assertFalse($this->policy->manageMeetings($user, $pgi));
    }

    public function test_usuario_com_permissao_pode_criar_pgi(): void
    {
        $user = $this->userWithMember(null, ['pgis.index.create']);

        $this->assertTrue($this->policy->create($user));
    }

    /**
     * @param  list<string>  $permissions
     */
    private function userWithMember(?Member $member, array $permissions): User
    {
        $user = $this->getMockBuilder(User::class)
            ->onlyMethods(['hasPermission'])
            ->getMock();

        $user->is_admin = false;
        $user->method('hasPermission')->willReturnCallback(
            static fn (string $key): bool => in_array($key, $permissions, true)
        );

        if ($member !== null) {
            $user->setRelation('member', $member);
        }

        return $user;
    }

    private function makeMember(int $id, ?int $pgiId = null): Member
    {
        $member = new Member(['pgi_id' => $pgiId]);
        $member->id = $id;

        return $member;
    }
}
