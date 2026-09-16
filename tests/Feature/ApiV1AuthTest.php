<?php

namespace Tests\Feature;

use App\Models\DeviceToken;
use App\Models\User;
use App\Services\Members\MemberUserService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApiV1AuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->boolean('must_change_password')->default(false);
            $table->boolean('is_admin')->default(false);
            $table->unsignedBigInteger('member_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
        });

        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('platform', 16);
            $table->string('token');
            $table->string('device_name')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    protected function tearDown(): void
    {
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('device_tokens');
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('users');
        parent::tearDown();
    }

    public function test_login_devolve_token_e_perfil(): void
    {
        $this->admin();

        $response = $this->postJson('/api/v1/login', [
            'email' => 'admin@adelss.test',
            'password' => 'secret123',
            'device_name' => 'Pixel 8',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.user.email', 'admin@adelss.test')
            ->assertJsonPath('data.user.is_admin', true)
            ->assertJsonPath('data.user.must_change_password', false)
            ->assertJsonPath('data.permissions.0', '*')
            ->assertJsonPath('data.token_type', 'Bearer');

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertIsArray($response->json('data.modules'));
    }

    public function test_login_invalido_nao_revela_se_o_email_existe(): void
    {
        $this->postJson('/api/v1/login', [
            'email' => 'ninguém@adelss.test',
            'password' => 'errada',
        ])->assertUnauthorized()
            ->assertJsonPath('error', 'E-mail ou senha inválidos.');
    }

    public function test_me_exige_token(): void
    {
        $this->getJson('/api/v1/me')
            ->assertUnauthorized()
            ->assertJsonPath('error', 'Não autenticado.');
    }

    public function test_me_e_logout_com_token(): void
    {
        $token = $this->loginToken();

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.user.email', 'admin@adelss.test');

        $this->withToken($token)
            ->postJson('/api/v1/logout')
            ->assertOk()
            ->assertJsonPath('data.logged_out', true);

        $this->assertDatabaseCount('personal_access_tokens', 0);

        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertUnauthorized();
    }

    public function test_precisa_trocar_senha_padrao_antes_de_registrar_dispositivo(): void
    {
        $user = $this->admin(['must_change_password' => true]);
        $token = $this->loginToken('secret123', $user);

        $this->withToken($token)
            ->getJson('/api/v1/me')
            ->assertOk()
            ->assertJsonPath('data.user.must_change_password', true);

        $this->withToken($token)
            ->postJson('/api/v1/devices', [
                'token' => 'fcm-abc',
                'platform' => 'android',
            ])
            ->assertForbidden()
            ->assertJsonPath('code', 'must_change_password');

        $this->withToken($token)
            ->postJson('/api/v1/password', [
                'password' => 'novaSenha9',
                'password_confirmation' => 'novaSenha9',
            ])
            ->assertOk()
            ->assertJsonPath('data.must_change_password', false);

        $this->withToken($token)
            ->postJson('/api/v1/devices', [
                'token' => 'fcm-abc',
                'platform' => 'android',
                'device_name' => 'Pixel 8',
            ])
            ->assertCreated();

        $this->assertSame(1, DeviceToken::query()->count());
    }

    public function test_nao_aceita_senha_padrao_na_troca(): void
    {
        $user = $this->admin(['must_change_password' => true]);
        $token = $this->loginToken('secret123', $user);

        $this->withToken($token)
            ->postJson('/api/v1/password', [
                'password' => MemberUserService::DEFAULT_PASSWORD,
                'password_confirmation' => MemberUserService::DEFAULT_PASSWORD,
            ])
            ->assertStatus(422);
    }

    public function test_esqueci_senha_nao_revela_cadastro_e_envia_quando_existe(): void
    {
        Notification::fake();
        $this->admin();

        $this->postJson('/api/v1/password/forgot', [
            'email' => 'ausente@adelss.test',
        ])->assertOk()->assertJsonPath('data.sent', true);

        Notification::assertNothingSent();

        $this->postJson('/api/v1/password/forgot', [
            'email' => 'admin@adelss.test',
        ])->assertOk();

        Notification::assertSentTo(
            User::query()->where('email', 'admin@adelss.test')->first(),
            ResetPassword::class
        );
    }

    public function test_login_pode_registrar_token_fcm(): void
    {
        $this->admin();

        $this->postJson('/api/v1/login', [
            'email' => 'admin@adelss.test',
            'password' => 'secret123',
            'device_name' => 'Pixel',
            'platform' => 'android',
            'fcm_token' => 'fcm-login',
        ])->assertOk();

        $this->assertDatabaseHas('device_tokens', [
            'token' => 'fcm-login',
            'platform' => 'android',
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function admin(array $overrides = []): User
    {
        return User::query()->create(array_merge([
            'name' => 'Admin',
            'email' => 'admin@adelss.test',
            'password' => Hash::make('secret123'),
            'is_admin' => true,
            'must_change_password' => false,
        ], $overrides));
    }

    private function loginToken(string $password = 'secret123', ?User $user = null): string
    {
        $email = $user?->email ?? 'admin@adelss.test';
        if ($user === null) {
            $this->admin();
        }

        $response = $this->postJson('/api/v1/login', [
            'email' => $email,
            'password' => $password,
        ]);

        $response->assertOk();

        return (string) $response->json('data.token');
    }
}
