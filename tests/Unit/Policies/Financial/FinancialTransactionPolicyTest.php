<?php

namespace Tests\Unit\Policies\Financial;

use App\Models\FinancialTransaction;
use App\Models\User;
use App\Policies\Financial\FinancialTransactionPolicy;
use Tests\TestCase;

class FinancialTransactionPolicyTest extends TestCase
{
    private FinancialTransactionPolicy $policy;

    protected function setUp(): void
    {
        parent::setUp();
        $this->policy = new FinancialTransactionPolicy();
    }

    public function test_admin_tem_acesso_total(): void
    {
        $user = new User(['is_admin' => true]);
        $transaction = new FinancialTransaction(['type' => 'despesa']);

        $this->assertTrue($this->policy->viewAny($user));
        $this->assertTrue($this->policy->update($user, $transaction));
        $this->assertTrue($this->policy->createReceita($user));
    }

    public function test_usuario_com_permissao_de_receita_pode_editar_receita(): void
    {
        $user = $this->userWithPermissions(['financial.receitas.edit']);
        $transaction = new FinancialTransaction(['type' => 'receita']);

        $this->assertTrue($this->policy->update($user, $transaction));
    }

    public function test_usuario_sem_permissao_nao_pode_editar_despesa(): void
    {
        $user = $this->userWithPermissions(['financial.receitas.edit']);
        $transaction = new FinancialTransaction(['type' => 'despesa']);

        $this->assertFalse($this->policy->update($user, $transaction));
    }

    public function test_envio_de_comprovante_exige_permissao_de_receita(): void
    {
        $user = $this->userWithPermissions(['financial.despesas.manage']);
        $receita = new FinancialTransaction(['type' => 'receita']);
        $despesa = new FinancialTransaction(['type' => 'despesa']);

        $this->assertFalse($this->policy->sendReceipt($user, $receita));
        $this->assertFalse($this->policy->sendReceipt($user, $despesa));
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
