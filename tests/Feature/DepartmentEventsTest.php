<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DepartmentEvent;
use App\Models\Permission;
use App\Models\User;
use App\Support\ModuleMenu;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DepartmentEventsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:', 'app.key' => 'base64:'.base64_encode(str_repeat('a', 32))]);
        Schema::create('users', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->string('email'); $table->string('password');
            $table->boolean('is_admin')->default(false); $table->unsignedBigInteger('member_id')->nullable(); $table->timestamps();
        });
        (require database_path('migrations/2023_12_31_000001_create_departments_table.php'))->up();
        (require database_path('migrations/2024_01_02_000001_add_fields_to_departments_table.php'))->up();
        (require database_path('migrations/2024_01_02_000003_create_department_roles_table.php'))->up();
        Schema::create('members', function (Blueprint $table) {
            $table->id(); $table->string('name'); $table->timestamps(); $table->softDeletes();
        });
        // The shared layout includes a weekly agenda widget.
        Schema::create('events', function (Blueprint $table) {
            $table->id(); $table->dateTime('start_date'); $table->dateTime('end_date')->nullable();
        });
        foreach (['department_members', 'department_leaders'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id(); $table->unsignedBigInteger('department_id'); $table->unsignedBigInteger('member_id');
                $table->unsignedBigInteger('department_role_id')->nullable(); $table->timestamps();
            });
        }
        Schema::create('cost_center_departments', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('department_id'); $table->unsignedBigInteger('cost_center_id'); $table->timestamps();
        });
        $this->migration()->up();
        (require database_path('migrations/2026_11_24_110000_add_cost_and_banner_to_department_events_table.php'))->up();
        (require database_path('migrations/2026_11_23_130000_add_responsible_member_to_department_roles.php'))->up();
        (require database_path('migrations/2026_11_24_100000_create_department_role_assignments_table.php'))->up();
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_11_23_120000_create_department_events_table.php');
    }

    private function user(array $permissions = [], bool $admin = false): User
    {
        $user = User::create(['name' => 'Usuário', 'email' => uniqid().'@example.test', 'password' => 'test', 'is_admin' => $admin]);
        $user->setRelation('permissions', collect($permissions)->map(fn ($key) => new Permission(['key' => $key])));
        $user->setRelation('member', null);

        return $user;
    }

    private function department(string $name = 'Jovens'): Department
    {
        return Department::create(['name' => $name, 'status' => 'ativo']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge(['title' => 'Encontro anual', 'start_date' => '2027-03-10T09:00', 'end_date' => '2027-03-10T18:00', 'all_day' => '0', 'location' => 'Templo', 'description' => 'Planejamento'], $overrides);
    }

    public function test_complete_event_flow_preserves_department_links(): void
    {
        $this->withoutExceptionHandling();
        $department = $this->department();
        $member = DB::table('members')->insertGetId(['name' => 'Ana']);
        $role = $department->roles()->create(['name' => 'Líder', 'is_default' => true]);
        $department->members()->attach($member, ['department_role_id' => $role->id]);
        $department->leaders()->attach($member);
        DB::table('cost_center_departments')->insert(['department_id' => $department->id, 'cost_center_id' => 7]);
        $before = [];
        foreach (['departments', 'department_roles', 'department_members', 'department_leaders', 'cost_center_departments'] as $table) {
            $before[$table] = DB::table($table)->get()->toJson();
        }
        $this->actingAs($this->user(admin: true));
        $this->get(route('departments.index'))->assertOk()->assertSee('Calendário anual');
        $this->get(route('departments.show', $department))->assertOk()->assertSee('Calendário anual');
        $this->get(route('departments.events.create', [$department, 'date' => '2027-03-10']))->assertOk()->assertSee('2027-03-10T09:00');
        $this->post(route('departments.events.store', $department), $this->payload())->assertSessionHasNoErrors();
        $event = $department->events()->sole();
        $this->get(route('departments.events.show', [$department, $event]))->assertOk()->assertSee('Encontro anual')->assertSee('Editar evento')->assertSee('Excluir evento');
        $this->get(route('departments.events.edit', [$department, $event]))->assertOk();
        $this->put(route('departments.events.update', [$department, $event]), $this->payload(['title' => 'Encontro atualizado', 'location' => 'Salão']))->assertRedirect(route('departments.events.show', [$department, $event]));
        $this->get(route('departments.events.index', [$department, 'year' => 2027]))->assertOk()->assertSee('Encontro atualizado')->assertViewHas('months', fn ($months) => $months->count() === 12);
        $this->delete(route('departments.events.destroy', [$department, $event]))->assertRedirect(route('departments.events.index', [$department, 'year' => 2027]));
        $this->assertDatabaseMissing('department_events', ['id' => $event->id]);
        foreach ($before as $table => $rows) {
            $this->assertSame($rows, DB::table($table)->get()->toJson(), $table);
        }
    }

    public function test_calendar_includes_spanning_events_and_excludes_other_departments_and_years(): void
    {
        $department = $this->department();
        $span = $department->events()->create($this->payload(['title' => 'Virada', 'start_date' => '2026-12-31 20:00', 'end_date' => '2027-02-01 10:00']));
        $department->events()->create($this->payload(['title' => 'Sem término', 'start_date' => '2027-06-10 09:00', 'end_date' => null]));
        $department->events()->create($this->payload(['title' => 'Evento do ano passado', 'start_date' => '2026-03-10 09:00', 'end_date' => null]));
        $this->department('Outro')->events()->create($this->payload(['title' => 'Privado de outro departamento']));
        $this->actingAs($this->user(['servico.departments.view']));
        $response = $this->get(route('departments.events.index', [$department, 'year' => 2027]));
        $response->assertOk()->assertSee('Virada')->assertSee('Sem término')->assertDontSee('Evento do ano passado')->assertDontSee('Privado de outro departamento')->assertDontSee('Novo evento');
        $response->assertViewHas('events', fn ($events) => $events->count() === 2);
        $response->assertViewHas('months', fn ($months) => $months[0]['events']->contains($span) && $months[1]['events']->contains($span) && !$months[2]['events']->contains($span));
        $this->get(route('departments.events.show', [$department, $span]))->assertOk()->assertDontSee('Editar evento')->assertDontSee('Excluir evento');
        $this->get(route('departments.events.index', [$department, 'year' => 2028]))->assertOk()->assertSee('Nenhum evento cadastrado');
    }

    public function test_nested_routes_reject_events_from_another_department_and_deleted_parents(): void
    {
        $owner = $this->department();
        $other = $this->department('Outro');
        $event = $owner->events()->create($this->payload());
        $this->actingAs($this->user(admin: true));
        $this->get(route('departments.events.show', [$other, $event]))->assertNotFound();
        $this->get(route('departments.events.edit', [$other, $event]))->assertNotFound();
        $this->put(route('departments.events.update', [$other, $event]), $this->payload())->assertNotFound();
        $this->delete(route('departments.events.destroy', [$other, $event]))->assertNotFound();
        $owner->delete();
        $this->get(route('departments.events.index', $owner))->assertNotFound();
        $this->get(route('departments.events.show', [$owner, $event]))->assertNotFound();
        $this->assertDatabaseHas('department_events', ['id' => $event->id]);
    }

    public function test_guests_and_users_without_permissions_cannot_manage_events(): void
    {
        $department = $this->department();
        $event = $department->events()->create($this->payload());
        $this->get(route('departments.events.index', $department))->assertRedirect(route('login'));
        $this->post(route('departments.events.store', $department), $this->payload())->assertRedirect(route('login'));
        foreach ([[], ['servico.voluntarios.cadastro.manage'], ['agenda.events.manage']] as $permissions) {
            $this->actingAs($this->user($permissions));
            $this->getJson(route('departments.events.index', $department))->assertForbidden();
            $this->postJson(route('departments.events.store', $department), $this->payload())->assertForbidden();
        }
        $this->actingAs($this->user(['servico.departments.view']));
        $this->getJson(route('departments.events.create', $department))->assertForbidden();
        $this->postJson(route('departments.events.store', $department), $this->payload())->assertForbidden();
        $this->getJson(route('departments.events.edit', [$department, $event]))->assertForbidden();
        $this->putJson(route('departments.events.update', [$department, $event]), $this->payload())->assertForbidden();
        $this->deleteJson(route('departments.events.destroy', [$department, $event]))->assertForbidden();
    }

    public function test_existing_granular_permissions_and_manage_are_honored(): void
    {
        $department = $this->department();
        $this->actingAs($this->user(['servico.departments.create']));
        $this->get(route('departments.events.create', $department))->assertOk();
        $this->post(route('departments.events.store', $department), $this->payload())->assertRedirect();
        $event = $department->events()->sole();
        $this->actingAs($this->user(['servico.departments.edit']));
        $this->get(route('departments.events.edit', [$department, $event]))->assertOk();
        $this->put(route('departments.events.update', [$department, $event]), $this->payload())->assertRedirect();
        $this->deleteJson(route('departments.events.destroy', [$department, $event]))->assertForbidden();
        $this->actingAs($this->user(['servico.departments.delete']));
        $this->delete(route('departments.events.destroy', [$department, $event]))->assertRedirect();
        $this->actingAs($this->user(['servico.departments.manage']));
        $this->post(route('departments.events.store', $department), $this->payload())->assertRedirect();
        $event = $department->events()->sole();
        $this->get(route('departments.events.index', $department))->assertOk();
        $this->put(route('departments.events.update', [$department, $event]), $this->payload())->assertRedirect();
        $this->delete(route('departments.events.destroy', [$department, $event]))->assertRedirect();
    }

    public function test_validates_dates_year_and_title_and_supports_all_day_events(): void
    {
        $department = $this->department();
        $this->actingAs($this->user(admin: true));
        $url = route('departments.events.store', $department);
        $this->postJson($url, $this->payload(['title' => str_repeat('a', 192), 'location' => str_repeat('b', 192)]))->assertUnprocessable()->assertJsonValidationErrors(['title', 'location']);
        $this->postJson($url, $this->payload(['title' => '', 'end_date' => '2027-03-09T18:00']))->assertUnprocessable()->assertJsonValidationErrors('title');
        $this->postJson($url, $this->payload(['end_date' => '2027-03-09T18:00']))->assertUnprocessable()->assertJsonValidationErrors('end_date');
        $this->postJson($url, $this->payload(['start_date' => 'invalid']))->assertUnprocessable()->assertJsonValidationErrors('start_date');
        foreach (['abc', 1899, 9999] as $year) {
            $this->getJson(route('departments.events.index', [$department, 'year' => $year]))->assertUnprocessable()->assertJsonValidationErrors('year');
        }
        $this->getJson(route('departments.events.create', [$department, 'date' => '2027-02-30']))->assertUnprocessable();
        $this->post($url, $this->payload(['all_day' => '1', 'end_date' => '2027-03-10T08:00']))->assertSessionHasNoErrors();
        $event = $department->events()->sole();
        $this->assertSame('2027-03-10 00:00:00', $event->start_date->format('Y-m-d H:i:s'));
        $this->assertSame('2027-03-10 23:59:59', $event->end_date->format('Y-m-d H:i:s'));
        $this->assertTrue($event->all_day);
        $this->put(route('departments.events.update', [$department, $event]), $this->payload(['end_date' => null]))->assertSessionHasNoErrors();
        $this->assertFalse($event->fresh()->all_day);
        $this->assertNull($event->fresh()->end_date);
    }

    public function test_department_menu_is_independent_and_service_keeps_its_own_destination(): void
    {
        $items = collect(ModuleMenu::itemsFor($this->user(['servico.departments.view'])))->keyBy('key');
        $this->assertSame(route('departments.index'), $items['departments']['url']);
        $this->assertFalse($items->has('servico'));
        $items = collect(ModuleMenu::itemsFor($this->user(admin: true)))->keyBy('key');
        $this->assertSame(['departments.*'], $items['departments']['patterns']);
        $this->assertSame(route('voluntarios.cadastro.index'), $items['servico']['url']);
        $this->assertNotContains('departments.*', $items['servico']['patterns']);
        $this->actingAs($this->user(['servico.departments.view']));
        $this->assertStringNotContainsString('Departamentos', view('servico.partials.module-nav')->render());
    }

    public function test_new_migration_can_be_rolled_back_without_affecting_departments(): void
    {
        $department = $this->department();
        $this->migration()->down();
        $this->assertFalse(Schema::hasTable('department_events'));
        $this->assertDatabaseHas('departments', ['id' => $department->id]);
        $this->migration()->up();
        $this->assertTrue(Schema::hasTable('department_events'));
    }

    public function test_cost_and_banner_can_be_created_preserved_replaced_removed_and_deleted(): void
    {
        Storage::fake('public');
        $department = $this->department();
        $this->actingAs($this->user(admin: true));
        $this->get(route('departments.events.create', $department))->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)->assertSee('Custo do evento')->assertSee('Banner do evento');
        $this->post(route('departments.events.store', $department), $this->payload([
            'cost' => '1.234,56', 'banner_image' => UploadedFile::fake()->image('banner.jpg'),
        ]))->assertSessionHasNoErrors()->assertRedirect();
        $event = $department->events()->sole();
        $firstBanner = $event->banner_image;
        $this->assertSame('1234.56', $event->cost);
        $this->assertStringStartsWith('departments/events/banners/', $firstBanner);
        Storage::disk('public')->assertExists($firstBanner);
        $this->get(route('departments.events.show', [$department, $event]))->assertOk()
            ->assertSee('R$ 1.234,56')->assertSee($firstBanner);
        $this->get(route('departments.events.edit', [$department, $event]))->assertOk()
            ->assertSee('1.234,56')->assertSee('Remover banner atual')->assertSee($firstBanner);

        // Older clients that do not send the new optional fields must preserve them.
        $this->put(route('departments.events.update', [$department, $event]), $this->payload())->assertSessionHasNoErrors();
        $this->assertSame($firstBanner, $event->fresh()->banner_image);
        $this->assertSame('1234.56', $event->fresh()->cost);
        $this->put(route('departments.events.update', [$department, $event]), $this->payload([
            'cost' => '0', 'banner_image' => UploadedFile::fake()->image('novo.png'),
        ]))->assertSessionHasNoErrors();
        $secondBanner = $event->fresh()->banner_image;
        $this->assertSame('0.00', $event->fresh()->cost);
        $this->assertNotSame($firstBanner, $secondBanner);
        Storage::disk('public')->assertMissing($firstBanner);
        Storage::disk('public')->assertExists($secondBanner);
        $this->get(route('departments.events.show', [$department, $event]))->assertOk()->assertSee('R$ 0,00');

        $this->put(route('departments.events.update', [$department, $event]), $this->payload([
            'cost' => '', 'remove_banner' => '1',
        ]))->assertSessionHasNoErrors();
        $this->assertNull($event->fresh()->cost);
        $this->assertNull($event->fresh()->banner_image);
        Storage::disk('public')->assertMissing($secondBanner);
        $this->put(route('departments.events.update', [$department, $event]), $this->payload([
            'banner_image' => UploadedFile::fake()->image('final.jpg'),
        ]))->assertSessionHasNoErrors();
        $lastBanner = $event->fresh()->banner_image;
        $this->delete(route('departments.events.destroy', [$department, $event]))->assertRedirect();
        Storage::disk('public')->assertMissing($lastBanner);
        $this->assertDatabaseMissing('department_events', ['id' => $event->id]);
    }

    public function test_invalid_costs_and_uploads_leave_existing_event_and_banner_intact(): void
    {
        Storage::fake('public');
        $department = $this->department();
        $this->actingAs($this->user(admin: true));
        $this->post(route('departments.events.store', $department), $this->payload([
            'cost' => '50.90', 'banner_image' => UploadedFile::fake()->image('original.jpg'),
        ]))->assertSessionHasNoErrors();
        $event = $department->events()->sole();
        $before = $event->fresh()->getAttributes();
        $url = route('departments.events.update', [$department, $event]);
        foreach (['-1', 'abc', '1.234', '10000000000', '1e3', '1,234', '12.34,56', ['50']] as $cost) {
            $this->putJson($url, $this->payload(['cost' => $cost]))->assertUnprocessable()->assertJsonValidationErrors('cost');
        }
        foreach ([
            UploadedFile::fake()->create('arquivo.pdf', 10, 'application/pdf'),
            UploadedFile::fake()->image('grande.jpg')->size(5121),
            UploadedFile::fake()->createWithContent('arte.svg', '<svg xmlns="http://www.w3.org/2000/svg"></svg>'),
        ] as $file) {
            $this->putJson($url, $this->payload(['banner_image' => $file, 'remove_banner' => '1']))
                ->assertUnprocessable()->assertJsonValidationErrors('banner_image');
        }
        $this->assertSame($before, $event->fresh()->getAttributes());
        $this->assertSame([$event->banner_image], Storage::disk('public')->allFiles());
    }

    public function test_banner_upload_obeys_department_scope_and_write_permissions(): void
    {
        Storage::fake('public');
        $owner = $this->department();
        $other = $this->department('Outro');
        $event = $owner->events()->create($this->payload());
        $this->actingAs($this->user(['servico.departments.view']));
        $this->postJson(route('departments.events.store', $owner), $this->payload([
            'cost' => '10', 'banner_image' => UploadedFile::fake()->image('restrito.jpg'),
        ]))->assertForbidden();
        $this->putJson(route('departments.events.update', [$owner, $event]), $this->payload([
            'cost' => '10', 'banner_image' => UploadedFile::fake()->image('restrito.jpg'),
        ]))->assertForbidden();
        $this->actingAs($this->user(admin: true));
        $this->putJson(route('departments.events.update', [$other, $event]), $this->payload([
            'cost' => '10', 'banner_image' => UploadedFile::fake()->image('outro.jpg'),
        ]))->assertNotFound();
        $this->assertNull($event->fresh()->banner_image);
        $this->assertNull($event->fresh()->cost);
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_cost_and_banner_migration_preserves_existing_events(): void
    {
        $event = $this->department()->events()->create($this->payload());
        $migration = require database_path('migrations/2026_11_24_110000_add_cost_and_banner_to_department_events_table.php');
        $before = $event->fresh()->getAttributes();
        unset($before['cost'], $before['banner_image']);
        $migration->down();
        $this->assertFalse(Schema::hasColumn('department_events', 'cost'));
        $this->assertSame($before, $event->fresh()->getAttributes());
        $migration->up();
        $this->assertSame($before, array_diff_key($event->fresh()->getAttributes(), array_flip(['cost', 'banner_image'])));
        $this->assertNull($event->fresh()->cost);
        $this->assertNull($event->fresh()->banner_image);
    }

    public function test_yearly_list_shows_each_matching_event_once_and_preserves_view_when_changing_year(): void
    {
        $department = $this->department();
        $department->events()->create($this->payload(['title' => 'Evento prolongado', 'start_date' => '2026-12-31 09:00', 'end_date' => '2027-02-01 09:00', 'cost' => '100.50']));
        $department->events()->create($this->payload(['title' => 'Fora do ano', 'start_date' => '2028-01-01 09:00', 'end_date' => null]));
        $this->department('Outro')->events()->create($this->payload(['title' => 'Evento de outro departamento']));
        $this->actingAs($this->user(['servico.departments.view']));
        $response = $this->get(route('departments.events.index', [$department, 'year' => 2027, 'view' => 'list']));
        $response->assertOk()->assertViewHas('display', 'list')->assertSee('Evento prolongado')->assertSee('R$ 100,50')
            ->assertDontSee('Fora do ano')->assertDontSee('Evento de outro departamento')->assertDontSee('Notificações');
        $this->assertSame(1, substr_count($response->getContent(), 'Evento prolongado'));
        $response->assertSee('view=list')->assertSee('year=2028');
        $this->getJson(route('departments.events.index', [$department, 'view' => 'invalid']))->assertUnprocessable();
        $this->get(route('departments.events.index', [$department, 'year' => 2028, 'view' => 'list']))->assertOk()->assertSee('Fora do ano');
    }
}
