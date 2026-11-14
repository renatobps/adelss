<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\MercadoPagoAccount;
use App\Models\User;
use App\Services\Payments\MercadoPagoService;
use App\Services\FinancialNotificationService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use MercadoPago\MercadoPagoConfig;
use MercadoPago\Net\MPHttpClient;
use MercadoPago\Net\MPRequest;
use MercadoPago\Net\MPResponse;
use Tests\TestCase;

class MercadoPagoEventAccountsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'app.url' => 'https://adelss.example.test']);
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email'); $t->string('password');
            $t->boolean('is_admin')->default(false); $t->unsignedBigInteger('member_id')->nullable(); $t->timestamps();
        });
        Schema::create('members', function (Blueprint $t) { $t->id(); $t->string('status')->nullable(); $t->softDeletes(); });
        Schema::create('events', function (Blueprint $t) {
            $t->id(); $t->string('title'); $t->string('public_slug')->nullable();
            $t->dateTime('start_date'); $t->dateTime('end_date')->nullable(); $t->timestamps();
        });
        Schema::create('event_registration_payments', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('event_registration_id')->nullable();
            $t->string('external_payment_id')->nullable(); $t->string('status')->default('pending');
            $t->string('status_detail')->nullable(); $t->string('payment_method')->nullable();
            $t->string('payer_email')->nullable(); $t->string('payer_document')->nullable();
            $t->text('qr_code_base64')->nullable(); $t->text('qr_code_text')->nullable();
            $t->timestamp('paid_at')->nullable(); $t->json('raw_payload')->nullable();
            $t->json('webhook_payload')->nullable(); $t->text('error_message')->nullable(); $t->timestamps();
        });
        Schema::create('event_registrations', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('event_id'); $t->string('status')->default('pendente');
            $t->timestamps(); $t->softDeletes();
        });
        (require base_path('database/migrations/2026_11_14_120000_create_mercado_pago_accounts_table.php'))->up();
        Http::preventStrayRequests();
        $this->app->instance(FinancialNotificationService::class, \Mockery::mock(FinancialNotificationService::class)->shouldIgnoreMissing());
    }

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => Hash::make('admin-password'), 'is_admin' => true]);
    }

    private function payload(array $override = []): array
    {
        return array_merge(['name' => 'Juventude', 'public_key' => 'public-key-demo', 'access_token' => 'secret-token-demo',
            'webhook_secret' => 'secret-signature-demo', 'statement_descriptor' => 'ADELSS', 'currency' => 'BRL',
            'notification_url' => 'https://adelss.example.test/webhooks/mercado-pago', 'sandbox' => false,
            'current_password' => 'admin-password'], $override);
    }

    private function account(array $override = []): MercadoPagoAccount
    {
        $data = $this->payload($override); unset($data['current_password']);
        return MercadoPagoAccount::create($data + ['uuid' => (string) Str::uuid(), 'merchant_id' => '123', 'merchant_name' => 'Conta de teste', 'is_active' => true]);
    }

    public function test_only_administrators_can_manage_accounts(): void
    {
        $this->get(route('admin.mercadopago.index'))->assertRedirect(route('login'));
        $user = User::create(['name' => 'User', 'email' => 'user@example.test', 'password' => Hash::make('password')]);
        $this->actingAs($user)->get(route('admin.mercadopago.index'))->assertForbidden();
        $this->post(route('admin.mercadopago.store'), $this->payload())->assertForbidden();
        Http::assertNothingSent();
    }

    public function test_saved_credentials_are_encrypted_and_not_displayed_in_the_admin_form(): void
    {
        Http::fake(['api.mercadopago.com/users/me' => Http::response(['id' => 123, 'nickname' => 'Juventude'], 200)]);
        $this->actingAs($this->admin())->post(route('admin.mercadopago.store'), $this->payload())->assertRedirect(route('admin.mercadopago.index'));
        $account = MercadoPagoAccount::firstOrFail();
        $raw = DB::table('mercado_pago_accounts')->first();
        $this->assertNotSame('secret-token-demo', $raw->access_token);
        $this->assertNotSame('secret-signature-demo', $raw->webhook_secret);
        $this->assertSame('secret-token-demo', $account->access_token);
        $this->assertArrayNotHasKey('access_token', $account->toArray());
        $this->assertArrayNotHasKey('webhook_secret', $account->toArray());
        $this->get(route('admin.mercadopago.edit', $account))->assertOk()
            ->assertDontSee('secret-token-demo')->assertDontSee('secret-signature-demo')
            ->assertHeader('Cache-Control', 'no-store, private');
    }

    public function test_password_is_required_and_validation_never_flashes_the_secrets(): void
    {
        $this->actingAs($this->admin())->from(route('admin.mercadopago.index'))
            ->post(route('admin.mercadopago.store'), $this->payload(['current_password' => 'wrong']))
            ->assertSessionHasErrors('current_password');
        $old = session()->getOldInput();
        foreach (['access_token', 'webhook_secret', 'current_password'] as $secret) $this->assertArrayNotHasKey($secret, $old);
        $this->assertSame(0, MercadoPagoAccount::count());
        Http::assertNothingSent();
    }

    public function test_invalid_token_does_not_change_configuration(): void
    {
        Http::fake(['api.mercadopago.com/users/me' => Http::response(['message' => 'invalid access token'], 401)]);
        $this->actingAs($this->admin())->post(route('admin.mercadopago.store'), $this->payload())->assertSessionHasErrors('access_token');
        $this->assertSame(0, MercadoPagoAccount::count());
    }

    public function test_webhook_urls_cannot_send_payment_data_to_another_server(): void
    {
        $this->actingAs($this->admin())->post(route('admin.mercadopago.store'), $this->payload([
            'notification_url' => 'https://another.example.test/webhooks/mercado-pago',
        ]))->assertSessionHasErrors('notification_url');
        Http::assertNothingSent();
    }

    public function test_credentials_cannot_be_managed_over_http_in_production(): void
    {
        $this->actingAs($this->admin());
        $this->app->instance('env', 'production');
        $this->get('http://localhost/admin/pagamentos/mercado-pago')->assertForbidden();
    }

    public function test_webhook_validation_explains_the_expected_url_and_rejects_wrong_urls_before_contacting_provider(): void
    {
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class);
        $this->actingAs($this->admin());
        $this->app->instance('env', 'production');
        foreach ([
            ['http://adelss.example.test/webhooks/mercado-pago', 'exige HTTPS'],
            ['https://adelss.example.test/webhooks/mercado-pago?account=example', 'somente no painel do Mercado Pago'],
            ['https://wrong.example.test/webhooks/mercado-pago', 'APP_URL'],
        ] as [$url, $reason]) {
            $this->post('https://adelss.example.test/admin/pagamentos/mercado-pago', $this->payload(['notification_url' => $url]))
                ->assertSessionHasErrors('notification_url');
            $message = session('errors')->first('notification_url');
            $this->assertStringContainsString('https://adelss.example.test/webhooks/mercado-pago', $message);
            $this->assertStringContainsString($reason, $message);
        }
        $this->assertSame(0, MercadoPagoAccount::count());
        Http::assertNothingSent();
    }

    public function test_production_form_suggests_https_even_when_app_url_uses_http(): void
    {
        $this->actingAs($this->admin());
        $this->app->instance('env', 'production');
        config(['app.url' => 'http://adelss.example.test']);
        $this->get('https://adelss.example.test/admin/pagamentos/mercado-pago')->assertOk()
            ->assertSee('value="https://adelss.example.test/webhooks/mercado-pago"', false)
            ->assertDontSee('value="http://adelss.example.test/webhooks/mercado-pago"', false);
    }

    public function test_rotation_keeps_old_credentials_and_payment_references(): void
    {
        $this->actingAs($this->admin());
        $old = $this->account();
        $event = Event::create(['title' => 'Imersão', 'start_date' => '2026-11-28 08:00:00', 'mercado_pago_account_id' => $old->id]);
        $paymentId = DB::table('event_registration_payments')->insertGetId(['external_payment_id' => 'old-charge', 'mercado_pago_account_id' => $old->id]);
        Http::fake(['api.mercadopago.com/users/me' => Http::response(['id' => 456, 'nickname' => 'Nova conta'], 200)]);
        $this->put(route('admin.mercadopago.update', $old), $this->payload([
            'public_key' => 'new-public-key', 'access_token' => 'new-token', 'webhook_secret' => 'new-secret',
        ]))->assertRedirect();
        $new = MercadoPagoAccount::where('is_active', true)->firstOrFail();
        $this->assertNotSame($old->id, $new->id);
        $this->assertFalse($old->fresh()->is_active);
        $this->assertSame('secret-token-demo', $old->fresh()->access_token);
        $this->assertSame($new->id, $event->fresh()->mercado_pago_account_id);
        $this->assertSame($old->id, DB::table('event_registration_payments')->where('id', $paymentId)->value('mercado_pago_account_id'));
    }

    public function test_blank_secrets_on_edit_preserve_the_stored_values(): void
    {
        $this->actingAs($this->admin()); $old = $this->account();
        Http::fake(['api.mercadopago.com/users/me' => Http::response(['id' => 123], 200)]);
        $this->put(route('admin.mercadopago.update', $old), $this->payload(['access_token' => '', 'webhook_secret' => '']))->assertRedirect();
        $new = MercadoPagoAccount::where('is_active', true)->firstOrFail();
        $this->assertSame('secret-token-demo', $new->access_token);
        $this->assertSame('secret-signature-demo', $new->webhook_secret);
    }

    public function test_changing_token_requires_the_new_webhook_secret(): void
    {
        $this->actingAs($this->admin()); $old = $this->account();
        $this->put(route('admin.mercadopago.update', $old), $this->payload(['access_token' => 'different-token', 'webhook_secret' => '']))->assertSessionHasErrors('webhook_secret');
        $this->assertTrue($old->fresh()->is_active); Http::assertNothingSent();
    }

    public function test_sdk_credentials_and_notification_urls_are_isolated_per_event_account(): void
    {
        config(['mercadopago.access_token' => 'financial-default-token']);
        $a = $this->account(['access_token' => 'event-a-token']);
        $b = $this->account(['access_token' => 'event-b-token', 'public_key' => 'event-b-public']);
        $client = new class implements MPHttpClient {
            public array $seen = [];
            public function send(MPRequest $request): MPResponse {
                $this->seen[] = ['token' => MercadoPagoConfig::getAccessToken(), 'payload' => json_decode($request->getPayload(), true)];
                return new MPResponse(201, ['id' => 999, 'status' => 'pending']);
            }
        };
        $previous = MercadoPagoConfig::getHttpClient(); MercadoPagoConfig::setHttpClient($client);
        try {
            $serviceA = new MercadoPagoService(); $serviceA->useAccount($a);
            $serviceB = new MercadoPagoService(); $serviceB->useAccount($b);
            $default = new MercadoPagoService();
            $payload = ['amount' => 30, 'description' => 'Teste', 'payer_email' => 'test@example.test', 'payer_document' => '00000000000', 'external_reference' => 'test'];
            $serviceA->createPixPayment($payload, 'id-a'); $serviceB->createPixPayment($payload, 'id-b');
            $serviceA->createPixPayment($payload, 'id-a2'); $default->createPixPayment($payload, 'id-default');
            $this->assertSame(['event-a-token', 'event-b-token', 'event-a-token', 'financial-default-token'], array_column($client->seen, 'token'));
            $this->assertStringContainsString('account='.$a->uuid, $client->seen[0]['payload']['notification_url']);
            $this->assertStringContainsString('account='.$b->uuid, $client->seen[1]['payload']['notification_url']);
            $this->assertSame('event-b-public', $serviceB->publicKey());
            $this->assertSame('financial-default-token', config('mercadopago.access_token'));
        } finally { MercadoPagoConfig::setHttpClient($previous); }
    }

    public function test_per_event_webhooks_always_require_a_valid_signature_even_in_test_mode(): void
    {
        $account = $this->account(['sandbox' => true]);
        $this->postJson(route('webhooks.mercadopago', ['account' => $account->uuid]), ['type' => 'payment', 'data' => ['id' => '999']])->assertUnauthorized();
        Http::assertNothingSent();
    }

    public function test_unsigned_legacy_webhooks_are_rejected_in_production(): void
    {
        config(['mercadopago.webhook_secret' => '', 'mercadopago.sandbox' => true]);
        $this->app->instance('env', 'production');
        $this->postJson(route('webhooks.mercadopago'), ['type' => 'payment', 'data' => ['id' => '999']])->assertUnauthorized();
    }

    public function test_archived_account_webhook_updates_only_its_original_payment(): void
    {
        $old = $this->account(['is_active' => false]);
        DB::table('event_registration_payments')->insert(['external_payment_id' => '777', 'mercado_pago_account_id' => $old->id]);
        $service = \Mockery::mock(MercadoPagoService::class);
        $service->shouldReceive('useAccount')->once()->with(\Mockery::on(fn ($a) => $a->id === $old->id));
        $service->shouldReceive('getPayment')->once()->with('777')->andReturn(['id' => 777, 'status' => 'in_process', 'collector_id' => 123]);
        $this->app->instance(MercadoPagoService::class, $service);
        $ts = (string) time(); $requestId = 'request-test';
        $signature = hash_hmac('sha256', "id:777;request-id:{$requestId};ts:{$ts};", 'secret-signature-demo');
        $this->withHeaders(['x-request-id' => $requestId, 'x-signature' => "ts={$ts},v1={$signature}"])
            ->postJson(route('webhooks.mercadopago', ['account' => $old->uuid]), ['type' => 'payment', 'data' => ['id' => '777']])->assertOk();
        $this->assertSame('in_process', DB::table('event_registration_payments')->where('external_payment_id', '777')->value('status'));
    }
    public function test_changing_an_event_account_requires_an_administrator(): void
    {
        $account = $this->account();
        $user = User::create(['name' => 'Editor', 'email' => 'editor@example.test', 'password' => Hash::make('password')]);
        $request = \Illuminate\Http\Request::create('/', 'POST', ['title' => 'Evento', 'start_date' => '2026-11-28', 'mercado_pago_account_id' => $account->id]);
        $request->setUserResolver(fn () => $user);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        (new \ReflectionMethod(\App\Http\Controllers\Agenda\EventosController::class, 'validateEvent'))
            ->invoke(new \App\Http\Controllers\Agenda\EventosController(), $request);
    }

    public function test_administrator_can_select_an_active_account_but_not_an_archived_one(): void
    {
        $admin = $this->admin(); $this->actingAs($admin);
        $account = $this->account();
        $request = \Illuminate\Http\Request::create('/', 'POST', ['title' => 'Evento', 'start_date' => '2026-11-28', 'mercado_pago_account_id' => $account->id]);
        $request->setUserResolver(fn () => $admin);
        $method = new \ReflectionMethod(\App\Http\Controllers\Agenda\EventosController::class, 'validateEvent');
        $controller = new \App\Http\Controllers\Agenda\EventosController();
        $this->assertSame($account->id, (int) $method->invoke($controller, $request)['base']['mercado_pago_account_id']);
        $account->update(['is_active' => false]);
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $method->invoke($controller, $request);
    }

    public function test_payment_settings_are_rate_limited(): void
    {
        $this->actingAs($this->admin());
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('admin.mercadopago.store'), $this->payload(['current_password' => 'wrong']))->assertRedirect();
        }
        $this->post(route('admin.mercadopago.store'), $this->payload())->assertStatus(429);
        Http::assertNothingSent();
    }

    private function checkoutSchema(): void
    {
        Schema::table('events', function (Blueprint $t) {
            $t->string('visibility')->default('public'); $t->string('status')->default('agendado');
            $t->boolean('registration_enabled')->default(true); $t->boolean('is_paid')->default(true);
            $t->decimal('price', 10, 2)->default(30);
        });
        Schema::create('event_registration_fields', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('event_id'); $t->integer('sort_order')->default(0); });
        Schema::table('event_registrations', function (Blueprint $t) {
            $t->string('name')->nullable(); $t->string('email')->nullable(); $t->string('phone')->nullable();
            $t->string('address')->nullable(); $t->json('custom_answers')->nullable();
        });
        Schema::table('event_registration_payments', function (Blueprint $t) {
            $t->string('idempotency_key')->nullable(); $t->string('external_reference')->nullable();
            $t->decimal('amount', 10, 2)->default(0); $t->string('currency')->default('BRL');
        });
        $this->app->instance(\App\Services\EventRegistrationReceiptService::class, \Mockery::mock(\App\Services\EventRegistrationReceiptService::class)->shouldIgnoreMissing());
        $whatsapp = \Mockery::mock(\App\Services\WhatsAppService::class);
        $whatsapp->shouldReceive('isConfigurado')->andReturn(false);
        $this->app->instance(\App\Services\WhatsAppService::class, $whatsapp);
    }

    public function test_registration_records_the_event_account_that_generated_the_charge(): void
    {
        $this->checkoutSchema();
        $account = $this->account();
        $event = Event::create(['title' => 'Imersão', 'public_slug' => 'imersao-profile', 'start_date' => '2026-11-28 08:00:00', 'mercado_pago_account_id' => $account->id]);
        $service = \Mockery::mock(MercadoPagoService::class);
        $service->shouldReceive('useAccount')->once()->with(\Mockery::on(fn ($a) => $a->id === $account->id));
        $service->shouldReceive('createPixPayment')->once()->andReturn(['id' => 3030, 'status' => 'pending', 'point_of_interaction' => ['transaction_data' => ['qr_code' => 'test-pix-code']]]);
        $this->app->instance(MercadoPagoService::class, $service);
        $this->from('/evento/imersao-profile')->post(route('events.public.register', $event->public_slug), [
            'name' => 'Participante teste', 'email' => 'participant@example.test', 'payer_document' => '00000000000',
            'payment_method' => 'pix', 'payment_account_version' => (string) $account->id,
        ])->assertRedirect('/evento/imersao-profile')->assertSessionHas('pix_payment');
        $this->assertSame($account->id, DB::table('event_registration_payments')->where('external_payment_id', '3030')->value('mercado_pago_account_id'));
        Http::assertNothingSent();
    }

    public function test_stale_checkout_is_rejected_before_creating_a_payment(): void
    {
        $this->checkoutSchema();
        $account = $this->account();
        Event::create(['title' => 'Imersão', 'public_slug' => 'stale-checkout', 'start_date' => '2026-11-28', 'mercado_pago_account_id' => $account->id]);
        $service = \Mockery::mock(MercadoPagoService::class);
        $service->shouldReceive('useAccount')->once(); $service->shouldNotReceive('createPixPayment');
        $this->app->instance(MercadoPagoService::class, $service);
        $this->from('/evento/stale-checkout')->post(route('events.public.register', 'stale-checkout'), ['payment_account_version' => 'default'])
            ->assertRedirect('/evento/stale-checkout')->assertSessionHas('error');
        $this->assertSame(0, DB::table('event_registration_payments')->count());
    }

    public function test_one_account_cannot_update_another_accounts_payment(): void
    {
        $a = $this->account(); $b = $this->account(['webhook_secret' => 'account-b-secret']);
        DB::table('event_registration_payments')->insert(['external_payment_id' => '777', 'mercado_pago_account_id' => $a->id]);
        $service = \Mockery::mock(MercadoPagoService::class);
        $service->shouldReceive('useAccount')->once();
        $service->shouldReceive('getPayment')->once()->andReturn(['id' => 777, 'status' => 'in_process', 'collector_id' => 123]);
        $this->app->instance(MercadoPagoService::class, $service);
        $ts = (string) time(); $requestId = 'request-b';
        $signature = hash_hmac('sha256', "id:777;request-id:{$requestId};ts:{$ts};", 'account-b-secret');
        $this->withHeaders(['x-request-id' => $requestId, 'x-signature' => "ts={$ts},v1={$signature}"])
            ->postJson(route('webhooks.mercadopago', ['account' => $b->uuid]), ['type' => 'payment', 'data' => ['id' => '777']])->assertOk();
        $this->assertSame('pending', DB::table('event_registration_payments')->where('external_payment_id', '777')->value('status'));
    }

}
