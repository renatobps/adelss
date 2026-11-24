<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DepartmentEvent;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DepartmentAgendaCalendarTest extends TestCase
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
            $table->id(); $table->string('name'); $table->string('status')->default('ativo'); $table->softDeletes(); $table->timestamps();
        });
        foreach ([
            '2023_12_31_000001_create_departments_table.php',
            '2024_01_02_000001_add_fields_to_departments_table.php',
            '2026_01_11_000301_create_event_categories_table.php',
            '2026_01_11_000338_create_events_table.php',
            '2026_11_23_120000_create_department_events_table.php',
            '2026_11_24_110000_add_cost_and_banner_to_department_events_table.php',
        ] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        Schema::table('events', fn (Blueprint $table) => $table->string('status')->default('agendado'));
    }

    private function user(array $permissions = [], bool $admin = false): User
    {
        $user = User::create(['name' => 'Teste', 'email' => uniqid().'@example.test', 'password' => 'test', 'is_admin' => $admin]);
        $user->setRelation('permissions', collect($permissions)->map(fn ($key) => new Permission(['key' => $key])));
        $user->setRelation('member', null);

        return $user;
    }

    private function department(): Department
    {
        return Department::create(['name' => 'Jovens', 'status' => 'ativo', 'color' => '#123456']);
    }

    private function eventData(array $overrides = []): array
    {
        return array_merge(['title' => 'Encontro', 'start_date' => '2027-01-10 09:00:00', 'end_date' => '2027-01-10 11:00:00', 'all_day' => false, 'location' => 'Templo', 'description' => 'Planejamento'], $overrides);
    }

    private function feed(array $range = [])
    {
        return $this->getJson(route('agenda.events.index', $range));
    }

    public function test_agenda_preserves_native_events_and_adds_department_events_without_copies_or_id_collisions(): void
    {
        $department = $this->department();
        $category = EventCategory::create(['name' => 'Culto', 'color' => '#0088cc']);
        $native = Event::create($this->eventData(['title' => 'Evento da Agenda', 'category_id' => $category->id]));
        $event = $department->events()->create($this->eventData());
        $this->assertSame($native->id, $event->id);
        $this->actingAs($this->user());
        $items = collect($this->feed()->assertOk()->json())->keyBy('id');
        $this->assertCount(2, $items);
        $this->assertSame('Evento da Agenda', $items[$native->id]['title']);
        $this->assertSame($category->id, $items[$native->id]['category_id']);
        $this->assertSame('#0088cc', $items[$native->id]['backgroundColor']);
        $item = $items['department-event:'.$event->id];
        $this->assertSame('Encontro — Jovens', $item['title']);
        $this->assertSame('#123456', $item['backgroundColor']);
        $this->assertSame('2027-01-10T09:00:00', $item['start']);
        $this->assertSame('department', $item['extendedProps']['sourceType']);
        $this->assertSame($department->id, $item['extendedProps']['departmentId']);
        $this->assertSame('Planejamento', $item['extendedProps']['description']);
        $this->assertNull($item['extendedProps']['detailUrl']);
        $this->assertNull($item['extendedProps']['editUrl']);
        $this->assertFalse($item['editable']);
        $this->assertDatabaseCount('events', 1);
        $this->assertDatabaseCount('department_events', 1);
    }

    public function test_department_crud_is_immediately_reflected_in_the_agenda_feed(): void
    {
        $department = $this->department();
        $this->actingAs($this->user(['servico.departments.manage']));
        $data = $this->eventData(['start_date' => '2027-01-10T09:00', 'end_date' => '2027-01-10T11:00', 'all_day' => '0']);
        $this->post(route('departments.events.store', $department), $data)->assertSessionHasNoErrors();
        $event = $department->events()->sole();
        $january = ['start' => '2027-01-01', 'end' => '2027-02-01'];
        $this->feed($january)->assertOk()->assertJsonCount(1)->assertJsonPath('0.title', 'Encontro — Jovens');
        $data['title'] = 'Evento atualizado';
        $data['start_date'] = '2027-02-10T10:00';
        $data['end_date'] = '2027-02-10T12:00';
        $this->put(route('departments.events.update', [$department, $event]), $data)->assertSessionHasNoErrors();
        $this->feed($january)->assertOk()->assertExactJson([]);
        $this->feed(['start' => '2027-02-01', 'end' => '2027-03-01'])->assertOk()->assertJsonPath('0.title', 'Evento atualizado — Jovens');
        $this->delete(route('departments.events.destroy', [$department, $event]))->assertRedirect();
        $this->feed()->assertOk()->assertExactJson([]);
        $this->assertDatabaseCount('events', 0);
    }

    public function test_both_sources_use_overlap_and_exclusive_range_end_including_zero_duration_events(): void
    {
        $department = $this->department();
        $expected = [];
        foreach ([
            ['2026-12-31 22:00', '2027-01-02 08:00', true],
            ['2026-12-01 00:00', '2027-03-01 00:00', true],
            ['2027-01-01 00:00', null, true],
            ['2027-01-01 00:00', '2027-01-01 00:00', true],
            ['2026-12-31 20:00', '2027-01-01 00:00', false],
            ['2027-02-01 00:00', '2027-02-01 10:00', false],
            ['2026-12-31 23:00', null, false],
        ] as [$start, $end, $included]) {
            $native = Event::create($this->eventData(['start_date' => $start, 'end_date' => $end]));
            $event = $department->events()->create($this->eventData(['start_date' => $start, 'end_date' => $end]));
            if ($included) { $expected[] = (string) $native->id; $expected[] = 'department-event:'.$event->id; }
        }
        $this->actingAs($this->user());
        $actual = collect($this->feed(['start' => '2027-01-01T00:00:00-03:00', 'end' => '2027-02-01T00:00:00-03:00'])->assertOk()->json())
            ->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->assertEqualsCanonicalizing($expected, $actual);
    }

    public function test_request_offsets_are_converted_to_the_application_timezone(): void
    {
        $department = $this->department();
        $inside = $department->events()->create($this->eventData(['start_date' => '2027-01-01 23:30:00', 'end_date' => null]));
        $department->events()->create($this->eventData(['start_date' => '2027-01-02 00:00:00', 'end_date' => null]));
        $this->actingAs($this->user());
        $this->feed(['start' => '2027-01-01T03:00:00Z', 'end' => '2027-01-02T03:00:00Z'])->assertOk()
            ->assertJsonCount(1)->assertJsonPath('0.id', 'department-event:'.$inside->id);
    }

    public function test_all_day_end_dates_are_exclusive_in_fullcalendar_without_mutating_the_inclusive_storage(): void
    {
        $department = $this->department();
        $span = $department->events()->create($this->eventData(['all_day' => true, 'start_date' => '2028-02-28 00:00:00', 'end_date' => '2028-03-01 23:59:59']));
        $single = $department->events()->create($this->eventData(['all_day' => true, 'start_date' => '2028-02-29 00:00:00', 'end_date' => null]));
        $this->actingAs($this->user());
        $items = collect($this->feed()->assertOk()->json())->keyBy('id');
        $this->assertSame('2028-02-28', $items['department-event:'.$span->id]['start']);
        $this->assertSame('2028-03-02', $items['department-event:'.$span->id]['end']);
        $this->assertSame('2028-03-01', $items['department-event:'.$single->id]['end']);
        $this->assertSame('2028-03-01 23:59:59', $span->fresh()->end_date->format('Y-m-d H:i:s'));
        $this->assertNull($single->fresh()->end_date);
    }

    public function test_deleted_departments_are_excluded_and_archived_departments_keep_their_events(): void
    {
        $deleted = $this->department();
        $deleted->events()->create($this->eventData());
        $deleted->delete();
        $archived = $this->department();
        $archived->update(['status' => 'arquivado']);
        $kept = $archived->events()->create($this->eventData());
        $this->actingAs($this->user());
        $this->feed()->assertOk()->assertJsonCount(1)->assertJsonPath('0.id', 'department-event:'.$kept->id);
        $this->assertDatabaseCount('department_events', 2);
    }

    public function test_department_management_links_follow_department_permissions_not_agenda_permissions(): void
    {
        $department = $this->department();
        $event = $department->events()->create($this->eventData());
        $this->actingAs($this->user(['agenda.events.manage']));
        $this->feed()->assertOk()->assertJsonPath('0.extendedProps.editUrl', null);
        $this->getJson(route('departments.events.edit', [$department, $event]))->assertForbidden();
        $this->deleteJson(route('departments.events.destroy', [$department, $event]))->assertForbidden();
        $this->actingAs($this->user(['servico.departments.view']));
        $this->feed()->assertOk()->assertJsonPath('0.extendedProps.detailUrl', route('departments.events.show', [$department, $event]))->assertJsonPath('0.extendedProps.editUrl', null);
        $this->actingAs($this->user(['servico.departments.edit']));
        $this->feed()->assertOk()->assertJsonPath('0.extendedProps.editUrl', route('departments.events.edit', [$department, $event]));
        $this->actingAs($this->user(admin: true));
        $this->feed()->assertOk()->assertJsonPath('0.extendedProps.editUrl', route('departments.events.edit', [$department, $event]));
    }

    public function test_department_ids_cannot_be_used_to_change_a_native_event_with_the_same_number(): void
    {
        $native = Event::create($this->eventData());
        $event = $this->department()->events()->create($this->eventData());
        $this->assertSame($native->id, $event->id);
        $this->actingAs($this->user(admin: true));
        $url = '/agenda/events/department-event:'.$event->id;
        $this->getJson($url)->assertNotFound();
        $this->putJson($url, ['title' => 'Alteração indevida'])->assertNotFound();
        $this->deleteJson($url)->assertNotFound();
        $this->getJson(route('agenda.events.show', $native))->assertOk()->assertJsonPath('event.title', 'Encontro');
        $this->assertDatabaseHas('events', ['id' => $native->id, 'title' => 'Encontro']);
        $this->assertDatabaseHas('department_events', ['id' => $event->id, 'title' => 'Encontro']);
    }

    public function test_calendar_modal_is_available_to_readers_and_invalid_ranges_are_rejected(): void
    {
        $this->get(route('agenda.calendario.index'))->assertRedirect(route('login'));
        $this->getJson(route('agenda.events.index'))->assertUnauthorized();
        $this->actingAs($this->user());
        $this->get(route('agenda.calendario.index'))->assertOk()->assertSee('departmentEventModal')->assertSee('Editar em Departamentos')->assertDontSee('id="addEventModal"', false);
        foreach ([['start' => 'invalid', 'end' => '2027-02-01'], ['start' => '2027-02-01'], ['start' => '2027-02-01', 'end' => '2027-01-01']] as $range) {
            $this->feed($range)->assertUnprocessable();
        }
    }
}
