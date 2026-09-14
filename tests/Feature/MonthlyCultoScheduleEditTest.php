<?php

namespace Tests\Feature;

use App\Http\Controllers\MonthlyCultoScheduleController;
use App\Models\Event;
use App\Models\Member;
use App\Models\MonthlyCultoSchedule;
use App\Models\ServiceArea;
use App\Models\User;
use App\Models\Volunteer;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MonthlyCultoScheduleEditTest extends TestCase
{
    private ServiceArea $culto;

    private ServiceArea $abertura;

    private ServiceArea $profetico;

    private ServiceArea $salaCriancas;

    private ServiceArea $zeladoria;

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
            $table->boolean('is_admin')->default(false);
            $table->unsignedBigInteger('member_id')->nullable();
            $table->timestamps();
        });

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('status')->default(Member::STATUS_ATIVO);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('volunteers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('member_id');
            $table->string('experience_level')->nullable();
            $table->date('start_date')->nullable();
            $table->string('status')->default('ativo');
            $table->text('leader_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('service_areas', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default('ativo');
            $table->unsignedBigInteger('leader_id')->nullable();
            $table->unsignedInteger('min_quantity')->nullable();
            $table->string('allowed_audience')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedInteger('sort_order')->nullable();
            $table->string('whatsapp_group_jid')->nullable();
            $table->string('whatsapp_group_name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('volunteer_service_areas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('volunteer_id');
            $table->unsignedBigInteger('service_area_id');
            $table->timestamps();
        });

        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->string('location')->nullable();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });

        Schema::create('monthly_culto_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('event_id');
            $table->unsignedInteger('month');
            $table->unsignedInteger('year');
            $table->string('status')->default('rascunho');
            $table->string('guest_preletor_name')->nullable();
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

        $this->culto = ServiceArea::create(['name' => 'Culto', 'status' => 'ativo', 'min_quantity' => 1, 'sort_order' => 1]);
        $this->abertura = ServiceArea::create(['name' => 'Abertura do culto', 'status' => 'ativo', 'min_quantity' => 1, 'parent_id' => $this->culto->id, 'sort_order' => 1]);
        $this->profetico = ServiceArea::create(['name' => 'Momento profético', 'status' => 'ativo', 'min_quantity' => 1, 'parent_id' => $this->culto->id, 'sort_order' => 2]);
        $this->salaCriancas = ServiceArea::create(['name' => 'Sala das Crianças', 'status' => 'ativo', 'min_quantity' => 3, 'sort_order' => 4]);
        $this->zeladoria = ServiceArea::create(['name' => 'Zeladoria', 'status' => 'ativo', 'min_quantity' => 2, 'sort_order' => 6]);
    }

    public function test_edicao_lista_as_subareas_do_culto_com_os_voluntarios_da_area_pai(): void
    {
        $volunteer = $this->volunteer('Ana Souza', $this->culto);
        $escala = $this->schedule($this->sundayEvent());

        $data = $this->editData($escala);

        $areaNames = $data['serviceAreas']->pluck('name')->all();
        $this->assertContains('Abertura do culto', $areaNames);
        $this->assertContains('Momento profético', $areaNames);

        $this->assertArrayHasKey($this->abertura->id, $data['volunteersByArea']);
        $this->assertSame(
            [$volunteer->id],
            $data['volunteersByArea'][$this->abertura->id]->pluck('id')->all()
        );
        $this->assertSame(['Abertura do culto'], $data['slotLabelsByArea'][$this->abertura->id]);
    }

    public function test_edicao_marca_os_voluntarios_ja_escalados_na_subarea(): void
    {
        $volunteer = $this->volunteer('Bruno Lima', $this->culto);
        $escala = $this->schedule($this->sundayEvent());
        $this->assign($escala, $this->profetico, $volunteer);

        $data = $this->editData($escala);

        $this->assertSame([$volunteer->id], $data['selectedVolunteersByArea'][$this->profetico->id]);
        $this->assertSame([], $data['selectedVolunteersByArea'][$this->abertura->id]);
    }

    public function test_edicao_preserva_escala_gravada_na_area_principal(): void
    {
        $volunteer = $this->volunteer('Diego Melo', $this->culto);
        $escala = $this->schedule($this->sundayEvent());
        $this->assign($escala, $this->culto, $volunteer);

        $data = $this->editData($escala);

        $this->assertSame([$volunteer->id], $data['selectedVolunteersByArea'][$this->culto->id]);
        $this->assertSame(
            [$volunteer->id],
            $data['volunteersByArea'][$this->culto->id]->pluck('id')->all()
        );
    }

    public function test_culto_de_domingo_mostra_sala_das_criancas_e_zeladoria(): void
    {
        $escala = $this->schedule($this->sundayEvent());

        $areaNames = $this->editData($escala)['serviceAreas']->pluck('name')->all();

        $this->assertContains('Sala das Crianças', $areaNames);
        $this->assertContains('Zeladoria', $areaNames);
    }

    public function test_culto_de_quarta_nao_mostra_sala_das_criancas_nem_zeladoria(): void
    {
        $escala = $this->schedule($this->wednesdayEvent());

        $areaNames = $this->editData($escala)['serviceAreas']->pluck('name')->all();

        $this->assertNotContains('Sala das Crianças', $areaNames);
        $this->assertNotContains('Zeladoria', $areaNames);
        $this->assertContains('Abertura do culto', $areaNames);
    }

    public function test_update_salva_voluntarios_nas_subareas(): void
    {
        $ana = $this->volunteer('Ana Souza', $this->culto);
        $bruno = $this->volunteer('Bruno Lima', $this->culto);
        $escala = $this->schedule($this->wednesdayEvent());

        $response = $this->actingAs($this->admin())->put(
            route('voluntarios.escalas-mensais.update', $escala),
            ['service_areas' => [
                $this->abertura->id => [$ana->id],
                $this->profetico->id => [$bruno->id, ''],
            ]]
        );

        $response->assertRedirect(route('voluntarios.escalas-mensais.index', ['month' => $escala->month, 'year' => $escala->year]));
        $this->assertSame(
            [$this->abertura->id => $ana->id, $this->profetico->id => $bruno->id],
            DB::table('monthly_culto_service_areas')
                ->where('monthly_culto_schedule_id', $escala->id)
                ->pluck('volunteer_id', 'service_area_id')
                ->map(fn ($id) => (int) $id)
                ->all()
        );
    }

    public function test_update_recusa_area_de_domingo_em_culto_de_quarta(): void
    {
        $volunteer = $this->volunteer('Carla Dias', $this->salaCriancas);
        $escala = $this->schedule($this->wednesdayEvent());

        $response = $this->actingAs($this->admin())->put(
            route('voluntarios.escalas-mensais.update', $escala),
            ['service_areas' => [$this->salaCriancas->id => [$volunteer->id]]]
        );

        $response->assertSessionHas('error', 'A escala de Sala das Crianças é somente para os cultos de domingo.');
        $this->assertSame(0, DB::table('monthly_culto_service_areas')->count());
    }

    public function test_area_somente_domingo_reconhecida_pelo_nome(): void
    {
        $this->assertTrue($this->salaCriancas->isSundayOnly());
        $this->assertTrue($this->zeladoria->isSundayOnly());
        $this->assertTrue((new ServiceArea(['name' => 'Limpeza']))->isSundayOnly());
        $this->assertFalse($this->culto->isSundayOnly());
        $this->assertFalse($this->abertura->isSundayOnly());
    }

    /**
     * @return array<string, mixed>
     */
    private function editData(MonthlyCultoSchedule $escala): array
    {
        $this->be($this->admin());

        return app(MonthlyCultoScheduleController::class)->edit($escala)->getData();
    }

    private function admin(): User
    {
        return User::firstOrCreate(
            ['email' => 'admin@adelss.test'],
            ['name' => 'Admin', 'password' => bcrypt('secret'), 'is_admin' => true]
        );
    }

    private function volunteer(string $name, ServiceArea $area): Volunteer
    {
        $member = Member::create(['name' => $name, 'status' => Member::STATUS_ATIVO]);
        $volunteer = Volunteer::create([
            'member_id' => $member->id,
            'experience_level' => 'novo',
            'start_date' => now()->toDateString(),
            'status' => 'ativo',
        ]);
        $volunteer->serviceAreas()->attach($area->id);

        return $volunteer;
    }

    private function sundayEvent(): Event
    {
        return Event::create([
            'title' => 'Culto da Família',
            'start_date' => Carbon::create(2026, 9, 6, 18, 0),
        ]);
    }

    private function wednesdayEvent(): Event
    {
        return Event::create([
            'title' => 'Culto da Graça',
            'start_date' => Carbon::create(2026, 9, 9, 19, 30),
        ]);
    }

    private function schedule(Event $event): MonthlyCultoSchedule
    {
        return MonthlyCultoSchedule::create([
            'event_id' => $event->id,
            'month' => (int) $event->start_date->month,
            'year' => (int) $event->start_date->year,
            'status' => 'rascunho',
        ]);
    }

    private function assign(MonthlyCultoSchedule $escala, ServiceArea $area, Volunteer $volunteer): void
    {
        $escala->serviceAreaVolunteers()->attach($volunteer->id, [
            'service_area_id' => $area->id,
            'status' => 'pendente',
        ]);
    }
}
