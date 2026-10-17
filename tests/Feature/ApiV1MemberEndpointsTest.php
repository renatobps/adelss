<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Meeting;
use App\Models\Member;
use App\Models\MonthlyCultoSchedule;
use App\Models\MoriahSchedule;
use App\Models\Pgi;
use App\Models\ServiceArea;
use App\Models\ServiceSchedule;
use App\Models\ServiceScheduleArea;
use App\Models\ServiceScheduleVolunteer;
use App\Models\User;
use App\Models\Volunteer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ApiV1MemberEndpointsTest extends TestCase
{
    private User $user;

    private Member $member;

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

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('gender', 1)->nullable();
            $table->string('marital_status')->nullable();
            $table->date('birth_date')->nullable();
            $table->date('marriage_date')->nullable();
            $table->date('membership_date')->nullable();
            $table->string('photo_url')->nullable();
            $table->string('cpf')->nullable();
            $table->string('rg')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state', 2)->nullable();
            $table->string('zip_code')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->timestamp('geocoded_at')->nullable();
            $table->string('status')->default('ativo');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('pgi_id')->nullable();
            $table->unsignedBigInteger('role_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('financial_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('financial_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('type')->nullable();
            $table->date('transaction_date')->nullable();
            $table->string('description')->nullable();
            $table->decimal('amount', 12, 2)->default(0);
            $table->boolean('is_paid')->default(true);
            $table->unsignedBigInteger('member_id')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('account_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('school_id')->nullable();
            $table->string('schedule')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('class_students', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('class_id');
            $table->unsignedBigInteger('member_id');
            $table->timestamps();
        });

        Schema::create('discipleship_cycles', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('discipleship_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cycle_id')->nullable();
            $table->unsignedBigInteger('member_id');
            $table->unsignedBigInteger('discipulador_id')->nullable();
            $table->string('status')->default('ativo');
            $table->date('data_inicio')->nullable();
            $table->date('data_fim')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('volunteers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->string('status')->default('ativo');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('service_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('service_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->date('date');
            $table->time('start_time')->nullable();
            $table->string('status')->default('publicada');
            $table->string('location')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('service_schedule_areas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_id');
            $table->unsignedBigInteger('service_area_id');
            $table->timestamps();
        });

        Schema::create('service_schedule_volunteers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('schedule_area_id');
            $table->unsignedBigInteger('volunteer_id');
            $table->string('status')->default('pendente');
            $table->timestamps();
        });

        Schema::create('event_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color')->nullable();
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->boolean('all_day')->default(false);
            $table->string('visibility')->default('public');
            $table->string('status')->default('agendado');
            $table->string('location')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->timestamps();
        });

        Schema::create('monthly_culto_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->string('status')->default('publicada');
            $table->timestamps();
        });

        Schema::create('monthly_culto_service_areas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('monthly_culto_schedule_id');
            $table->unsignedBigInteger('service_area_id');
            $table->unsignedBigInteger('volunteer_id');
            $table->string('status')->default('pendente');
            $table->timestamps();
        });

        Schema::create('moriah_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id')->nullable();
            $table->string('title')->nullable();
            $table->date('date');
            $table->time('time')->nullable();
            $table->string('status')->default('publicada');
            $table->boolean('request_confirmation')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('moriah_schedule_members', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('moriah_schedule_id');
            $table->unsignedBigInteger('member_id');
            $table->string('status')->default('pendente');
            $table->timestamps();
        });

        Schema::create('member_roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('permission_user', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('user_id');
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('name')->nullable();
            $table->string('module')->nullable();
            $table->timestamps();
        });

        Schema::create('pgis', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('day_of_week')->nullable();
            $table->string('time_schedule')->nullable();
            $table->string('address')->nullable();
            $table->string('neighborhood')->nullable();
            $table->string('number')->nullable();
            $table->unsignedBigInteger('leader_1_id')->nullable();
            $table->unsignedBigInteger('leader_2_id')->nullable();
            $table->unsignedBigInteger('leader_training_1_id')->nullable();
            $table->unsignedBigInteger('leader_training_2_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pgi_id');
            $table->date('meeting_date');
            $table->string('subject')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('participants_count')->default(0);
            $table->unsignedInteger('visitors_count')->default(0);
            $table->timestamp('attendance_registered_at')->nullable();
            $table->unsignedBigInteger('attendance_registered_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('meeting_attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('meeting_id');
            $table->unsignedBigInteger('member_id')->nullable();
            $table->string('visitor_name')->nullable();
            $table->string('visitor_phone')->nullable();
            $table->string('type')->default('participant');
            $table->timestamps();
        });

        $this->member = Member::query()->create([
            'name' => 'Ana',
            'email' => 'ana@igreja.test',
            'status' => Member::STATUS_ATIVO,
        ]);

        $this->user = User::query()->create([
            'name' => 'Ana',
            'email' => 'ana@igreja.test',
            'password' => Hash::make('secret123'),
            'is_admin' => false,
            'must_change_password' => false,
            'member_id' => $this->member->id,
        ]);
    }

    public function test_lista_e_confirma_escala_de_servico_e_moriah(): void
    {
        $volunteer = Volunteer::query()->create([
            'member_id' => $this->member->id,
            'status' => 'ativo',
        ]);
        $area = ServiceArea::query()->create(['name' => 'Louvor']);
        $schedule = ServiceSchedule::query()->create([
            'title' => 'Culto de domingo',
            'date' => now()->addDay()->toDateString(),
            'start_time' => '19:00:00',
            'status' => 'publicada',
            'location' => 'Templo',
        ]);
        $scheduleArea = ServiceScheduleArea::query()->create([
            'schedule_id' => $schedule->id,
            'service_area_id' => $area->id,
        ]);
        $assignment = ServiceScheduleVolunteer::query()->create([
            'schedule_area_id' => $scheduleArea->id,
            'volunteer_id' => $volunteer->id,
            'status' => 'pendente',
        ]);

        $moriah = MoriahSchedule::query()->create([
            'title' => 'Louvor Moriah',
            'date' => now()->addDays(2)->toDateString(),
            'time' => '18:30:00',
            'status' => 'publicada',
            'request_confirmation' => true,
        ]);
        $moriahPivotId = DB::table('moriah_schedule_members')->insertGetId([
            'moriah_schedule_id' => $moriah->id,
            'member_id' => $this->member->id,
            'status' => 'pendente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $list = $this->auth()->getJson('/api/v1/me/schedules');
        $list->assertOk();
        $this->assertCount(2, $list->json('data'));
        $this->assertSame(2, $list->json('meta.pending'));

        $this->auth()->postJson('/api/v1/me/schedules/service/'.$assignment->id.'/confirm')
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmado');

        $this->auth()->postJson('/api/v1/me/schedules/moriah/'.$moriahPivotId.'/confirm')
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmado');

        $other = Member::query()->create(['name' => 'Outro', 'status' => Member::STATUS_ATIVO]);
        $otherPivot = DB::table('moriah_schedule_members')->insertGetId([
            'moriah_schedule_id' => $moriah->id,
            'member_id' => $other->id,
            'status' => 'pendente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->auth()->postJson('/api/v1/me/schedules/moriah/'.$otherPivot.'/reject')
            ->assertNotFound();
    }

    public function test_pgi_e_reunioes_do_membro(): void
    {
        $pgi = Pgi::query()->create([
            'name' => 'PGI Centro',
            'day_of_week' => 'quarta',
            'time_schedule' => '20h',
            'address' => 'Rua A',
            'leader_1_id' => $this->member->id,
        ]);
        $this->member->update(['pgi_id' => $pgi->id]);

        $meeting = Meeting::query()->create([
            'pgi_id' => $pgi->id,
            'meeting_date' => now()->subWeek()->toDateString(),
            'subject' => 'Oração',
            'participants_count' => 8,
        ]);

        $this->auth()->getJson('/api/v1/me/pgi')
            ->assertOk()
            ->assertJsonPath('data.name', 'PGI Centro')
            ->assertJsonPath('data.is_leader', true)
            ->assertJsonPath('data.can_manage_meetings', true);

        $this->auth()->getJson('/api/v1/me/pgi/meetings')
            ->assertOk()
            ->assertJsonPath('data.0.subject', 'Oração');

        $this->auth()->getJson('/api/v1/me/pgi/meetings/'.$meeting->id)
            ->assertOk()
            ->assertJsonPath('data.id', $meeting->id);

        $other = Member::query()->create([
            'name' => 'Bia',
            'status' => Member::STATUS_ATIVO,
            'pgi_id' => $pgi->id,
        ]);

        $this->auth()->getJson('/api/v1/me/pgi/meetings/'.$meeting->id.'/attendance')
            ->assertOk()
            ->assertJsonPath('data.members.0.present', false);

        $this->auth()->putJson('/api/v1/me/pgi/meetings/'.$meeting->id.'/attendance', [
            'participants' => [$this->member->id, $other->id],
            'visitors' => [['name' => 'Visitante', 'phone' => '61999999999']],
            'notes' => 'Boa reunião',
        ])
            ->assertOk()
            ->assertJsonPath('data.attendance_registered', true)
            ->assertJsonPath('data.participants_count', 2)
            ->assertJsonPath('data.visitors_count', 1);

        $created = $this->auth()->postJson('/api/v1/me/pgi/meetings', [
            'meeting_date' => now()->toDateString(),
            'subject' => 'Nova reunião',
        ]);
        $created->assertCreated()->assertJsonPath('data.subject', 'Nova reunião');

        $this->auth()->getJson('/api/v1/me/home')
            ->assertOk()
            ->assertJsonPath('data.pgi.name', 'PGI Centro')
            ->assertJsonPath('data.can_view_financial', false);

        $this->auth()->getJson('/api/v1/financial/summary')
            ->assertForbidden()
            ->assertJsonPath('code', 'financial_forbidden');
    }

    public function test_agenda_lista_eventos_do_periodo(): void
    {
        Event::query()->create([
            'title' => 'Culto da Graça',
            'start_date' => now()->addDays(3)->setTime(19, 0),
            'status' => 'agendado',
            'visibility' => 'public',
            'location' => 'Templo',
        ]);
        Event::query()->create([
            'title' => 'Cancelado',
            'start_date' => now()->addDays(4),
            'status' => 'cancelado',
            'visibility' => 'public',
        ]);

        $response = $this->auth()->getJson('/api/v1/agenda/events');
        $response->assertOk();
        $titles = collect($response->json('data'))->pluck('title');
        $this->assertTrue($titles->contains('Culto da Graça'));
        $this->assertFalse($titles->contains('Cancelado'));
    }

    public function test_sem_membro_nao_lista_escalas(): void
    {
        $orphan = User::query()->create([
            'name' => 'Sem membro',
            'email' => 'sem@igreja.test',
            'password' => Hash::make('secret123'),
            'is_admin' => true,
            'must_change_password' => false,
        ]);

        $token = $orphan->createToken('test')->plainTextToken;
        $this->withToken($token)
            ->getJson('/api/v1/me/schedules')
            ->assertForbidden()
            ->assertJsonPath('code', 'member_required');
    }

    public function test_perfil_completo_edicao_contribuicoes_ensino_e_discipulado(): void
    {
        $dizimo = \App\Models\FinancialCategory::query()->create(['name' => 'Dízimo']);
        $oferta = \App\Models\FinancialCategory::query()->create(['name' => 'Oferta']);
        $conta = \App\Models\FinancialAccount::query()->create(['name' => 'Caixa']);

        \App\Models\FinancialTransaction::query()->create([
            'member_id' => $this->member->id,
            'category_id' => $dizimo->id,
            'account_id' => $conta->id,
            'transaction_date' => now()->subDays(3)->toDateString(),
            'description' => 'Dízimo março',
            'amount' => 150.50,
            'is_paid' => true,
        ]);
        \App\Models\FinancialTransaction::query()->create([
            'member_id' => $this->member->id,
            'category_id' => $oferta->id,
            'account_id' => $conta->id,
            'transaction_date' => now()->subDay()->toDateString(),
            'description' => 'Oferta',
            'amount' => 40,
            'is_paid' => true,
        ]);

        $turma = \App\Models\Turma::query()->create([
            'name' => 'Discipulado 1',
            'schedule' => 'Domingo 9h',
            'status' => 'ativa',
        ]);
        $this->member->turmas()->attach($turma->id);

        $mentor = User::query()->create([
            'name' => 'Pastor',
            'email' => 'pastor@igreja.test',
            'password' => Hash::make('secret123'),
            'is_admin' => false,
            'must_change_password' => false,
        ]);

        \App\Models\Discipleship\DiscipleshipMember::query()->create([
            'member_id' => $this->member->id,
            'cycle_id' => \App\Models\Discipleship\DiscipleshipCycle::query()->create(['nome' => 'Ciclo 2026'])->id,
            'discipulador_id' => $mentor->id,
            'status' => 'ativo',
            'data_inicio' => now()->subMonths(2)->toDateString(),
        ]);

        $this->auth()->getJson('/api/v1/me/profile')
            ->assertOk()
            ->assertJsonPath('data.member.name', 'Ana')
            ->assertJsonPath('data.member.status', 'ativo')
            ->assertJsonPath('data.ensino_count', 1)
            ->assertJsonPath('data.discipleship_count', 1)
            ->assertJsonPath('data.contributions.dizimo_total', 150.5)
            ->assertJsonPath('data.contributions.oferta_total', 40);

        $updated = $this->auth()->putJson('/api/v1/me/profile', [
            'name' => 'Ana Silva',
            'email' => 'ana@igreja.test',
            'phone' => '61988887777',
            'gender' => 'F',
            'marital_status' => 'casado',
            'city' => 'Brasília',
            'state' => 'DF',
            'status' => 'inativo',
            'pgi_id' => 99,
        ]);
        $updated->assertOk()
            ->assertJsonPath('data.member.name', 'Ana Silva')
            ->assertJsonPath('data.member.phone', '61988887777')
            ->assertJsonPath('data.member.gender', 'F')
            ->assertJsonPath('data.member.status', 'ativo')
            ->assertJsonPath('data.member.pgi_id', null);

        $this->assertSame('Ana Silva', $this->user->fresh()->name);

        $this->auth()->getJson('/api/v1/me/contributions')
            ->assertOk()
            ->assertJsonPath('data.dizimo_total', 150.5)
            ->assertJsonPath('data.items.0.description', 'Oferta');

        $this->auth()->getJson('/api/v1/me/ensino')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Discipulado 1')
            ->assertJsonPath('data.0.role', 'aluno');

        $this->auth()->getJson('/api/v1/me/discipleship')
            ->assertOk()
            ->assertJsonPath('data.being_discipled.0.cycle', 'Ciclo 2026')
            ->assertJsonPath('data.being_discipled.0.mentor', 'Pastor');
    }

    public function test_modulo_de_membros_nao_depende_de_admin_para_existir_mas_respeita_permissao(): void
    {
        $this->auth()->getJson('/api/v1/members')
            ->assertForbidden()
            ->assertJsonPath('code', 'members_forbidden');

        $this->auth()->getJson('/api/v1/members/'.$this->member->id)
            ->assertOk()
            ->assertJsonPath('data.member.name', 'Ana');

        $admin = User::query()->create([
            'name' => 'Secretaria',
            'email' => 'secretaria@igreja.test',
            'password' => Hash::make('secret123'),
            'is_admin' => true,
            'must_change_password' => false,
        ]);
        $this->flushHeaders();
        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/members')
            ->assertOk()
            ->assertJsonPath('data.can_create', true)
            ->assertJsonPath('data.items.0.name', 'Ana');

        $created = $this->actingAs($admin, 'sanctum')->postJson('/api/v1/members', [
            'name' => 'Bruno Costa',
            'email' => 'bruno@igreja.test',
            'phone' => '61911112222',
            'status' => 'visitante',
        ]);
        $created->assertCreated()
            ->assertJsonPath('data.member.name', 'Bruno Costa')
            ->assertJsonPath('data.member.status', 'visitante');

        $id = $created->json('data.member.id');
        $this->actingAs($admin, 'sanctum')->putJson('/api/v1/members/'.$id, [
            'name' => 'Bruno Costa',
            'status' => 'ativo',
            'phone' => '61911112222',
        ])
            ->assertOk()
            ->assertJsonPath('data.member.status', 'ativo');

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/members/options')
            ->assertOk()
            ->assertJsonPath('data.statuses.0.value', 'ativo');

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/me/home')
            ->assertOk()
            ->assertJsonPath('data.can_view_members', true);
    }

    private function auth(): self
    {
        return $this->withToken($this->user->createToken('test')->plainTextToken);
    }
}
