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
            $table->string('status')->default('ativo');
            $table->unsignedBigInteger('pgi_id')->nullable();
            $table->unsignedBigInteger('role_id')->nullable();
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

    private function auth(): self
    {
        return $this->withToken($this->user->createToken('test')->plainTextToken);
    }
}
