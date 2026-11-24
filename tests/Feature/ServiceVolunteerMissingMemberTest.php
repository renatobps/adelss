<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\Permission;
use App\Models\ServiceArea;
use App\Models\ServiceHistory;
use App\Models\ServiceSchedule;
use App\Models\User;
use App\Models\Volunteer;
use App\Support\ModuleMenu;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ServiceVolunteerMissingMemberTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('email'); $table->string('password');
            $table->boolean('is_admin')->default(false); $table->unsignedBigInteger('member_id')->nullable(); $table->timestamps();
        });
        Schema::create('members', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('volunteers', function (Blueprint $table) {
            // No FK here so legacy orphaned IDs can also be reproduced.
            $table->id(); $table->unsignedBigInteger('member_id'); $table->string('experience_level');
            $table->date('start_date'); $table->string('status'); $table->text('leader_notes')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('service_areas', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('status')->default('ativo');
            $table->unsignedBigInteger('leader_id')->nullable(); $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('sort_order')->default(0); $table->integer('min_quantity')->default(1); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('volunteer_service_areas', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('volunteer_id'); $table->unsignedBigInteger('service_area_id'); $table->timestamps();
        });
        Schema::create('events', function (Blueprint $table) {
            $table->id(); $table->dateTime('start_date'); $table->dateTime('end_date')->nullable();
        });
        Schema::create('service_schedules', function (Blueprint $table) {
            $table->id(); $table->string('title'); $table->date('date'); $table->time('start_time');
            $table->string('status'); $table->string('type'); $table->string('location')->nullable(); $table->text('notes')->nullable();
            $table->unsignedBigInteger('event_id')->nullable(); $table->timestamps(); $table->softDeletes();
        });
        Schema::create('service_schedule_areas', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('schedule_id'); $table->unsignedBigInteger('service_area_id');
            $table->integer('required_quantity'); $table->unsignedBigInteger('responsible_id')->nullable(); $table->timestamps();
        });
        Schema::create('service_schedule_volunteers', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('schedule_area_id'); $table->unsignedBigInteger('volunteer_id');
            $table->string('status'); $table->text('notes')->nullable(); $table->timestamps();
        });
        Schema::create('service_histories', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('member_id'); $table->unsignedBigInteger('volunteer_id');
            $table->unsignedBigInteger('service_area_id'); $table->unsignedBigInteger('schedule_id');
            $table->date('date'); $table->string('status'); $table->string('service_type'); $table->text('notes')->nullable(); $table->timestamps();
        });
    }

    private function user(array $permissions = [], bool $admin = true): User
    {
        $user = User::create(['name' => 'Teste', 'email' => uniqid().'@example.test', 'password' => 'test', 'is_admin' => $admin]);
        $user->setRelation('permissions', collect($permissions)->map(fn ($key) => new Permission(['key' => $key])));
        $user->setRelation('member', null);

        return $user;
    }

    private function volunteer(bool $deletedMember = true): Volunteer
    {
        $member = Member::create(['name' => $deletedMember ? 'Membro removido' : 'Membro presente']);
        $volunteer = Volunteer::create(['member_id' => $member->id, 'experience_level' => 'novo', 'start_date' => '2026-01-01', 'status' => 'ativo']);
        if ($deletedMember) { $member->delete(); }

        return $volunteer;
    }

    public static function missingMemberCases(): array
    {
        return ['soft deleted' => [false], 'orphaned legacy ID' => [true]];
    }

    /** @dataProvider missingMemberCases */
    public function test_cadastro_renders_missing_member_without_hiding_or_changing_records(bool $orphan): void
    {
        $volunteer = $this->volunteer();
        if ($orphan) { $volunteer->update(['member_id' => 9999]); }
        $memberId = $volunteer->member_id;
        $area = ServiceArea::create(['name' => 'Recepção', 'status' => 'ativo']);
        $volunteer->serviceAreas()->attach($area);
        $present = $this->volunteer(false);
        $this->actingAs($this->user());
        $this->get(route('voluntarios.cadastro.index'))->assertOk()->assertSee('Membro indisponível')->assertSee('Membro presente')->assertViewHas('volunteers', fn ($rows) => $rows->count() === 2);
        $this->get(route('voluntarios.cadastro.show', $volunteer))->assertOk()->assertSee('Membro indisponível')->assertSee('foram preservados');
        $this->get(route('voluntarios.cadastro.edit', $volunteer))->assertOk()->assertSee('Selecione um membro disponível');
        $this->assertNull($volunteer->fresh()->member);
        $this->assertDatabaseHas('volunteers', ['id' => $volunteer->id, 'member_id' => $memberId, 'deleted_at' => null]);
        $this->assertDatabaseHas('volunteer_service_areas', ['volunteer_id' => $volunteer->id, 'service_area_id' => $area->id]);
        $this->assertSame(1, Member::onlyTrashed()->count());
        $this->assertSame($present->member_id, $present->fresh()->member->id);
    }

    public function test_reports_history_and_schedule_forms_handle_unavailable_members(): void
    {
        $volunteer = $this->volunteer();
        $area = ServiceArea::create(['name' => 'Recepção', 'status' => 'ativo']);
        $volunteer->serviceAreas()->attach($area);
        $schedule = ServiceSchedule::create(['title' => 'Culto de teste', 'date' => now()->toDateString(), 'start_time' => '19:00', 'status' => 'rascunho', 'type' => 'culto']);
        $scheduleArea = $schedule->areas()->create(['service_area_id' => $area->id, 'required_quantity' => 1]);
        $scheduleArea->volunteers()->create(['volunteer_id' => $volunteer->id, 'status' => 'confirmado']);
        $history = ServiceHistory::create(['member_id' => $volunteer->member_id, 'volunteer_id' => $volunteer->id, 'service_area_id' => $area->id, 'schedule_id' => $schedule->id, 'date' => now()->subDays(60)->toDateString(), 'status' => 'serviu', 'service_type' => 'culto']);
        $this->actingAs($this->user());
        foreach (['voluntarios.relatorios.top-volunteers', 'voluntarios.relatorios.inactive', 'voluntarios.relatorios.by-schedule'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('Membro indisponível');
        }
        $this->get(route('voluntarios.historico.index'))->assertOk();
        $this->get(route('voluntarios.historico.show', $history))->assertOk();
        $this->get(route('voluntarios.historico.volunteer', $volunteer))->assertOk();
        $wizard = ['step1' => ['title' => 'Teste', 'date' => now()->toDateString(), 'start_time' => '19:00', 'type' => 'culto'], 'step2' => ['areas' => [['service_area_id' => $area->id, 'required_quantity' => 1]]], 'step3' => ['volunteers' => [['schedule_area_id' => 0, 'volunteer_ids' => [$volunteer->id]]]]];
        $this->withSession(['schedule_wizard' => $wizard])->get(route('voluntarios.escalas.create', ['step' => 3]))->assertOk()->assertSee('Membro indisponível');
        $this->get(route('voluntarios.escalas.create', ['step' => 4]))->assertOk()->assertSee('Membro indisponível');
        $this->get(route('voluntarios.escalas.edit', $schedule))->assertOk()->assertSee('Membro indisponível');
        $volunteer->delete();
        $this->get(route('voluntarios.relatorios.top-volunteers'))->assertOk()->assertSee('Membro indisponível');
        $this->assertDatabaseHas('service_histories', ['id' => $history->id, 'member_id' => $volunteer->member_id]);
    }

    public function test_suggestions_exclude_deleted_members_and_keep_available_volunteers(): void
    {
        $missing = $this->volunteer();
        $present = $this->volunteer(false);
        $area = ServiceArea::create(['name' => 'Recepção', 'status' => 'ativo']);
        $area->volunteers()->attach([$missing->id, $present->id]);
        $this->actingAs($this->user())->getJson(route('voluntarios.escalas.api.suggested-volunteers', ['service_area_id' => $area->id, 'date' => now()->toDateString(), 'start_time' => '19:00']))
            ->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', $present->id);
        $this->assertDatabaseCount('volunteers', 2);
    }

    public function test_deleted_member_cannot_be_selected_for_new_or_updated_registration(): void
    {
        $volunteer = $this->volunteer();
        $member = Member::create(['name' => 'Outro removido']);
        $member->delete();
        $data = ['member_id' => $member->id, 'experience_level' => 'novo', 'start_date' => '2026-01-01', 'status' => 'ativo'];
        $this->actingAs($this->user());
        $this->postJson(route('voluntarios.cadastro.store'), $data)->assertUnprocessable()->assertJsonValidationErrors('member_id');
        $this->putJson(route('voluntarios.cadastro.update', $volunteer), $data)->assertUnprocessable()->assertJsonValidationErrors('member_id');
        $active = Member::create(['name' => 'Membro disponível']);
        $data['member_id'] = $active->id;
        $this->put(route('voluntarios.cadastro.update', $volunteer), $data)->assertRedirect(route('voluntarios.cadastro.index'));
        $this->assertDatabaseHas('volunteers', ['id' => $volunteer->id, 'member_id' => $active->id]);
        $this->assertSame(2, Member::onlyTrashed()->count());
    }

    public function test_service_access_and_menu_permissions_stay_independent_from_departments(): void
    {
        $this->actingAs($this->user(['servico.voluntarios.cadastro.view'], false));
        $this->get(route('voluntarios.cadastro.index'))->assertOk();
        $this->getJson(route('voluntarios.cadastro.create'))->assertForbidden();
        $items = collect(ModuleMenu::itemsFor(auth()->user()))->keyBy('key');
        $this->assertTrue($items->has('servico'));
        $this->assertFalse($items->has('departments'));
        $this->actingAs($this->user(['servico.departments.view'], false));
        $this->getJson(route('voluntarios.cadastro.index'))->assertForbidden();
        foreach (['areas', 'escalas', 'historico', 'relatorios'] as $resource) {
            $user = $this->user(['servico.voluntarios.'.$resource.'.view'], false);
            $items = collect(ModuleMenu::itemsFor($user))->keyBy('key');
            $this->assertTrue($items->has('servico'));
            $this->assertFalse($items->has('departments'));
        }
    }
}
