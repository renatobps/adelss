<?php

namespace Tests\Unit\Services;

use App\Models\Member;
use App\Models\User;
use App\Services\Members\MemberUserService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MemberUserServicePasswordTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('status')->default('ativo');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('must_change_password')->default(false);
            $table->boolean('is_admin')->default(false);
            $table->unsignedBigInteger('member_id')->nullable();
            $table->timestamps();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('members');
        parent::tearDown();
    }

    public function test_login_novo_com_senha_padrao_exige_troca(): void
    {
        $member = Member::query()->create([
            'name' => 'Maria',
            'email' => 'maria@igreja.test',
            'status' => Member::STATUS_ATIVO,
        ]);

        $user = (new MemberUserService())->syncFromMember($member);

        $this->assertTrue($user->must_change_password);
        $this->assertTrue(Hash::check(MemberUserService::DEFAULT_PASSWORD, $user->password));
    }

    public function test_senha_informada_nao_marca_troca_obrigatoria(): void
    {
        $member = Member::query()->create([
            'name' => 'João',
            'email' => 'joao@igreja.test',
            'status' => Member::STATUS_ATIVO,
        ]);

        $user = (new MemberUserService())->syncFromMember($member, 'senhaForte1');

        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('senhaForte1', $user->password));
    }
}
