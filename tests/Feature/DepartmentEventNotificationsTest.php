<?php

namespace Tests\Feature;

use App\Jobs\ProcessDepartmentEventNotificationBatch;
use App\Models\Department;
use App\Models\DepartmentEvent;
use App\Models\DepartmentEventNotificationBatch;
use App\Models\Member;
use App\Models\Permission;
use App\Models\User;
use App\Services\DepartmentEventNotificationService;
use App\Services\WhatsAppService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;

class DepartmentEventNotificationsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'app.key' => 'base64:'.base64_encode(str_repeat('a', 32)),
            'cache.default' => 'array', 'whatsapp.api_url' => 'https://wa.test', 'whatsapp.api_key' => 'global-token', 'whatsapp.instance_id' => 'instance-a']);
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('email'); $table->string('password');
            $table->boolean('is_admin')->default(false); $table->unsignedBigInteger('member_id')->nullable(); $table->timestamps();
        });
        Schema::create('members', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('phone')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('events', function (Blueprint $table) {
            $table->id(); $table->dateTime('start_date'); $table->dateTime('end_date')->nullable();
        });
        foreach (['2023_12_31_000001_create_departments_table.php', '2024_01_02_000001_add_fields_to_departments_table.php',
            '2024_01_02_000002_create_department_members_table.php', '2024_01_02_000003_create_department_roles_table.php',
            '2024_01_02_000004_add_foreign_key_department_role_to_department_members.php', '2026_01_14_145533_create_department_leaders_table.php',
            '2026_11_23_120000_create_department_events_table.php', '2026_11_24_110000_add_cost_and_banner_to_department_events_table.php',
            '2026_11_24_120000_create_department_event_notifications_tables.php'] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        Cache::flush();
        Bus::fake();
        Http::preventStrayRequests();
        $this->fakeWhatsApp();
    }

    private function fakeWhatsApp(bool $connected = true, bool $token = true, int $sendStatus = 200): void
    {
        Cache::forget('whatsapp.instances_snapshot');
        Http::swap(new \Illuminate\Http\Client\Factory());
        Http::preventStrayRequests();
        Http::fake([
            'https://wa.test/instance/all' => Http::response(['data' => [
                ['id' => 'instance-a', 'name' => 'Conta padrão', 'connected' => true, 'token' => 'token-a'],
                ['id' => 'instance-b', 'name' => 'Conta do evento', 'connected' => $connected, 'token' => $token ? 'token-b' : ''],
            ]]),
            'https://wa.test/send/text' => Http::response($sendStatus === 200 ? ['success' => true] : ['error' => 'indisponível'], $sendStatus),
        ]);
    }

    private function user(array $permissions = [], bool $admin = true): User
    {
        $user = User::create(['name' => 'Gestor', 'email' => uniqid().'@example.test', 'password' => 'test', 'is_admin' => $admin]);
        $user->setRelation('permissions', collect($permissions)->map(fn ($key) => new Permission(['key' => $key])));
        $user->setRelation('member', null);
        return $user;
    }

    private function event(string $title = 'Encontro'): DepartmentEvent
    {
        return Department::create(['name' => 'Jovens', 'status' => 'ativo'])->events()->create([
            'title' => $title, 'start_date' => '2027-03-10 09:00', 'location' => 'Templo',
        ]);
    }

    private function member(string $name = 'Ana', ?string $phone = '(61) 99999-1111'): Member
    {
        return Member::create(['name' => $name, 'phone' => $phone]);
    }

    private function payload(array $ids, array $overrides = []): array
    {
        return array_merge(['participants' => $ids, 'instance_id' => 'instance-b', 'message' => DepartmentEventNotificationService::DEFAULT_MESSAGE,
            'automatic_enabled' => '0', 'scheduled_at' => null, 'intent' => 'save', 'request_key' => (string) Str::uuid()], $overrides);
    }

    private function save(DepartmentEvent $event, array $payload)
    {
        return $this->put(route('departments.events.notifications.update', [$event->department, $event]), $payload);
    }

    public function test_notification_ui_and_participants_are_scoped_to_the_event_and_do_not_modify_department_membership(): void
    {
        $this->actingAs($this->user());
        $event = $this->event(); $other = $this->event('Outro'); $member = $this->member();
        $this->get(route('departments.notifications.index', $event->department))->assertOk()->assertSee('Notificações de eventos');
        $this->get(route('departments.events.notifications.edit', [$event->department, $event]))->assertOk()
            ->assertSee('Conta do evento')->assertSee('Participantes do evento')->assertDontSee('token-b')->assertDontSee('global-token');
        $this->save($event, $this->payload([$member->id]))->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame([$member->id], $event->participants->modelKeys());
        $this->assertSame(0, $other->participants()->count());
        $this->assertSame(0, DB::table('department_members')->count());
        $this->assertSame(0, DepartmentEventNotificationBatch::count());
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
        $this->get(route('departments.events.notifications.edit', [$other->department, $event]))->assertNotFound();
        $this->put(route('departments.events.notifications.update', [$other->department, $event]), $this->payload([$member->id]))->assertNotFound();
    }

    public function test_manual_send_is_idempotent_uses_only_selected_participants_and_does_not_change_active_instance(): void
    {
        $this->actingAs($this->user());
        $event = $this->event(); $member = $this->member(); $this->member('Não selecionado', '61999992222');
        Cache::forever('whatsapp.active_instance_id', 'instance-a');
        Cache::forever('whatsapp.active_instance_token', 'token-a');
        $payload = $this->payload([$member->id], ['intent' => 'send']);
        $this->save($event, $payload)->assertSessionHasNoErrors();
        $this->save($event, $payload)->assertSessionHasNoErrors();
        $this->assertSame(1, DepartmentEventNotificationBatch::count());
        $this->assertSame(1, DB::table('department_event_notification_deliveries')->count());
        Bus::assertDispatchedAfterResponse(ProcessDepartmentEventNotificationBatch::class);
        $service = app(DepartmentEventNotificationService::class);
        $this->assertSame(1, $service->processPending());
        $this->assertSame(0, $service->processPending());
        $this->assertDatabaseHas('department_event_notification_deliveries', ['member_id' => $member->id, 'status' => 'sent']);
        Http::assertSent(fn ($request) => $request->url() === 'https://wa.test/send/text'
            && $request->hasHeader('instanceId', 'instance-b') && $request->hasHeader('apikey', 'token-b')
            && $request['number'] === '5561999991111' && str_contains($request['text'], 'Olá, Ana!') && str_contains($request['text'], '10/03/2027'));
        $this->assertCount(1, Http::recorded(fn ($request) => $request->method() === 'POST'));
        $this->assertSame('instance-a', Cache::get('whatsapp.active_instance_id'));
        $this->assertSame('token-a', Cache::get('whatsapp.active_instance_token'));
    }

    public function test_automatic_send_runs_at_the_configured_time_once_and_accepts_a_new_schedule(): void
    {
        $this->travelTo(now()->setDate(2027, 3, 1)->setTime(8, 0));
        $this->actingAs($this->user());
        $event = $this->event(); $member = $this->member();
        $this->save($event, $this->payload([$member->id], ['automatic_enabled' => '1', 'scheduled_at' => '2027-03-01T09:00']))->assertSessionHasNoErrors();
        $this->artisan('departments:notify-events')->assertSuccessful();
        $this->assertSame(0, DepartmentEventNotificationBatch::count());
        $this->travelTo(now()->setTime(9, 1));
        $this->artisan('departments:notify-events')->assertSuccessful();
        $this->artisan('departments:notify-events')->assertSuccessful();
        $this->assertSame(1, DepartmentEventNotificationBatch::count());
        $this->assertDatabaseHas('department_event_notification_deliveries', ['status' => 'sent']);
        $this->save($event, $this->payload([$member->id], ['automatic_enabled' => '1', 'scheduled_at' => '2027-03-01T09:00', 'message' => 'Texto alterado']))->assertSessionHasNoErrors();
        $this->artisan('departments:notify-events')->assertSuccessful();
        $this->assertSame(1, DepartmentEventNotificationBatch::count());
        $this->save($event, $this->payload([$member->id], ['automatic_enabled' => '1', 'scheduled_at' => '2027-03-01T10:00']))->assertSessionHasNoErrors();
        $this->travelTo(now()->setTime(10, 1));
        $this->artisan('departments:notify-events')->assertSuccessful();
        $this->assertSame(2, DepartmentEventNotificationBatch::count());
        $this->travelBack();
    }

    public function test_invalid_duplicate_and_deleted_recipient_phones_are_not_sent(): void
    {
        $this->actingAs($this->user());
        $event = $this->event(); $first = $this->member(); $same = $this->member('Mesmo telefone');
        $missing = $this->member('Sem telefone', null); $invalid = $this->member('Inválido', '123');
        $deleted = $this->member('Excluído', '61999993333');
        $this->save($event, $this->payload([$first->id, $same->id, $missing->id, $invalid->id, $deleted->id], ['intent' => 'send']))->assertSessionHasNoErrors();
        $deleted->delete();
        app(DepartmentEventNotificationService::class)->processPending();
        $this->assertSame(1, DB::table('department_event_notification_deliveries')->where('status', 'sent')->count());
        $this->assertSame(3, DB::table('department_event_notification_deliveries')->where('status', 'skipped')->count());
        $this->assertSame(1, DB::table('department_event_notification_deliveries')->where('status', 'cancelled')->count());
    }

    public function test_instance_failure_never_falls_back_and_is_not_automatically_retried(): void
    {
        $this->actingAs($this->user()); $event = $this->event(); $member = $this->member();
        $this->save($event, $this->payload([$member->id], ['intent' => 'send']))->assertSessionHasNoErrors();
        $this->fakeWhatsApp(connected: false);
        app(DepartmentEventNotificationService::class)->processPending();
        $this->assertDatabaseHas('department_event_notification_deliveries', ['status' => 'failed']);
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
        $this->fakeWhatsApp(token: false);
        $result = app(WhatsAppService::class)->enviarMensagemNaInstancia('instance-b', $member->phone, 'Aviso');
        $this->assertFalse($result['success']);
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
        $this->fakeWhatsApp();
        $this->assertSame(0, app(DepartmentEventNotificationService::class)->processPending());
    }

    public function test_pending_sends_can_be_cancelled_and_removed_participants_are_not_notified(): void
    {
        $this->actingAs($this->user()); $event = $this->event(); $member = $this->member();
        $this->save($event, $this->payload([$member->id], ['intent' => 'send']))->assertSessionHasNoErrors();
        $event->participants()->detach($member);
        app(DepartmentEventNotificationService::class)->processPending();
        $this->assertDatabaseHas('department_event_notification_deliveries', ['status' => 'cancelled']);
        $this->save($event, $this->payload([$member->id], ['intent' => 'send']))->assertSessionHasNoErrors();
        $this->post(route('departments.events.notifications.cancel', [$event->department, $event]))->assertRedirect();
        $this->assertSame(0, DB::table('department_event_notification_deliveries')->where('status', 'pending')->count());
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }

    public function test_settings_validate_members_instances_and_schedules_and_reject_view_only_users(): void
    {
        $event = $this->event(); $member = $this->member(); $deleted = $this->member('Excluído'); $deleted->delete();
        $this->actingAs($this->user());
        foreach ([[$deleted->id], [99999], [$member->id, $member->id]] as $ids) {
            $this->save($event, $this->payload($ids))->assertSessionHasErrors();
        }
        $this->save($event, $this->payload([$member->id], ['instance_id' => 'invalid']))->assertSessionHasErrors('instance_id');
        $this->save($event, $this->payload([], ['intent' => 'send']))->assertSessionHasErrors('participants');
        $this->save($event, $this->payload([$member->id], ['automatic_enabled' => '1']))->assertSessionHasErrors('scheduled_at');
        $this->save($event, $this->payload([$member->id], ['automatic_enabled' => '1', 'scheduled_at' => '2020-01-01T09:00']))->assertSessionHasErrors('scheduled_at');
        $this->assertSame(0, DB::table('department_event_notifications')->count());
        $this->actingAs($this->user(['servico.departments.view'], false));
        $this->getJson(route('departments.notifications.index', $event->department))->assertForbidden();
        $this->getJson(route('departments.events.notifications.edit', [$event->department, $event]))->assertForbidden();
        $this->putJson(route('departments.events.notifications.update', [$event->department, $event]), $this->payload([$member->id]))->assertForbidden();
        $this->postJson(route('departments.events.notifications.cancel', [$event->department, $event]))->assertForbidden();
        $this->actingAs($this->user(['servico.departments.edit'], false));
        $this->save($event, $this->payload([$member->id]))->assertSessionHasNoErrors();
    }

    public function test_interrupted_deliveries_are_not_retried_and_deleted_events_cannot_be_sent(): void
    {
        $this->actingAs($this->user()); $event = $this->event(); $member = $this->member();
        $this->save($event, $this->payload([$member->id], ['intent' => 'send']))->assertSessionHasNoErrors();
        DB::table('department_event_notification_deliveries')->update(['status' => 'processing', 'claimed_at' => now()->subMinutes(10)]);
        $this->artisan('departments:notify-events')->assertSuccessful();
        $this->assertDatabaseHas('department_event_notification_deliveries', ['status' => 'unknown']);
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
        $this->save($event, $this->payload([$member->id], ['intent' => 'send']))->assertSessionHasNoErrors();
        $event->department->delete();
        $this->artisan('departments:notify-events')->assertSuccessful();
        $this->assertSame(0, DB::table('department_event_notification_deliveries')->where('status', 'pending')->count());
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }

    public function test_paused_or_replaced_automatic_schedule_cancels_its_pending_messages(): void
    {
        $this->travelTo(now()->setDate(2027, 3, 1)->setTime(8, 0));
        $this->actingAs($this->user()); $event = $this->event(); $member = $this->member();
        $this->save($event, $this->payload([$member->id], ['automatic_enabled' => '1', 'scheduled_at' => '2027-03-01T09:00']))->assertSessionHasNoErrors();
        $this->travelTo(now()->setTime(9, 1));
        $service = app(DepartmentEventNotificationService::class);
        $this->assertSame(1, $service->enqueueDue());
        $this->save($event, $this->payload([$member->id], ['automatic_enabled' => '1', 'scheduled_at' => '2027-03-01T10:00']))->assertSessionHasNoErrors();
        $service->processPending();
        $this->assertSame(1, DB::table('department_event_notification_deliveries')->where('status', 'cancelled')->count());
        $this->travelTo(now()->setTime(10, 1));
        $service->enqueueDue();
        $this->save($event, $this->payload([$member->id]))->assertSessionHasNoErrors();
        $service->processPending();
        $this->assertSame(2, DB::table('department_event_notification_deliveries')->where('status', 'cancelled')->count());
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
        $this->travelBack();
    }

    public function test_api_failures_are_recorded_without_retries_and_old_phone_numbers_are_cancelled(): void
    {
        $this->actingAs($this->user()); $event = $this->event(); $member = $this->member();
        $this->save($event, $this->payload([$member->id], ['intent' => 'send']))->assertSessionHasNoErrors();
        $this->fakeWhatsApp(sendStatus: 503);
        $service = app(DepartmentEventNotificationService::class);
        $service->processPending();
        $service->processPending();
        $this->assertDatabaseHas('department_event_notification_deliveries', ['status' => 'failed']);
        $this->assertCount(1, Http::recorded(fn ($request) => $request->method() === 'POST'));
        $this->fakeWhatsApp();
        $this->save($event, $this->payload([$member->id], ['intent' => 'send']))->assertSessionHasNoErrors();
        $member->update(['phone' => '61999998888']);
        $service->processPending();
        $this->assertDatabaseHas('department_event_notification_deliveries', ['status' => 'cancelled']);
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }

    public function test_guests_cannot_notify_and_deleting_an_event_removes_only_its_notification_data(): void
    {
        $event = $this->event(); $member = $this->member();
        $this->get(route('departments.events.notifications.edit', [$event->department, $event]))->assertRedirect(route('login'));
        $this->put(route('departments.events.notifications.update', [$event->department, $event]), $this->payload([$member->id]))->assertRedirect(route('login'));
        $this->actingAs($this->user());
        $this->save($event, $this->payload([$member->id], ['intent' => 'send']))->assertSessionHasNoErrors();
        $other = $this->event('Outro');
        $this->save($other, $this->payload([$member->id]))->assertSessionHasNoErrors();
        $this->delete(route('departments.events.destroy', [$event->department, $event]))->assertRedirect();
        $this->assertSame(0, DepartmentEventNotificationBatch::count());
        $this->assertSame(0, DB::table('department_event_notification_deliveries')->count());
        $this->assertDatabaseHas('department_event_participants', ['event_id' => $other->id, 'member_id' => $member->id]);
        $this->assertDatabaseHas('members', ['id' => $member->id]);
        Http::assertNotSent(fn ($request) => $request->method() === 'POST');
    }
}
