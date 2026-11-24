<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Member;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DepartmentRoleMembersTest extends TestCase
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
        foreach (['2023_12_31_000001_create_departments_table.php','2024_01_02_000001_add_fields_to_departments_table.php','2024_01_02_000002_create_department_members_table.php','2024_01_02_000003_create_department_roles_table.php','2024_01_02_000004_add_foreign_key_department_role_to_department_members.php','2026_01_14_145533_create_department_leaders_table.php','2026_11_23_120000_create_department_events_table.php','2026_11_23_130000_add_responsible_member_to_department_roles.php','2026_11_23_140000_create_department_projects_tables.php'] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        $this->migration()->up();
        Schema::table('departments', function (Blueprint $table) {
            $table->string('logo_url')->nullable(); $table->string('banner_url')->nullable(); $table->boolean('show_on_homepage')->default(false);
            $table->integer('homepage_order')->nullable(); $table->string('homepage_url')->nullable();
        });
        Schema::create('events', function (Blueprint $table) {
            $table->id(); $table->dateTime('start_date'); $table->dateTime('end_date')->nullable();
        });
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_11_24_100000_create_department_role_assignments_table.php');
    }

    private function department(string $name = 'Departamento'): Department
    {
        return Department::create(['name' => $name, 'status' => 'ativo']);
    }

    private function member(string $name): Member
    {
        return Member::create(['name' => $name]);
    }

    private function user(bool $admin = true): User
    {
        $user = User::create(['name' => 'Teste', 'email' => uniqid().'@example.test', 'password' => 'test', 'is_admin' => $admin]);
        $user->setRelation('member', null);
        $user->setRelation('permissions', collect($admin ? [] : [new Permission(['key' => 'servico.departments.view'])]));

        return $user;
    }

    public function test_migration_copies_all_existing_assignments_without_duplicates_or_changing_previous_tables(): void
    {
        $this->migration()->down();
        $department = $this->department();
        $ana = $this->member('Ana'); $bruno = $this->member('Bruno');
        $role = $department->roles()->create(['name' => 'Secretaria', 'responsible_member_id' => $ana->id]);
        $department->members()->attach($ana->id, ['department_role_id' => $role->id]);
        $department->members()->attach($bruno->id, ['department_role_id' => $role->id]);
        $bruno->delete();
        $before = [];
        foreach (['department_roles','department_members','members'] as $table) { $before[$table] = DB::table($table)->orderBy('id')->get()->toJson(); }
        $this->migration()->up();
        $this->assertDatabaseCount('department_role_assignments', 2);
        $this->assertDatabaseHas('department_role_assignments', ['department_role_id' => $role->id, 'member_id' => $ana->id]);
        $this->assertDatabaseHas('department_role_assignments', ['department_role_id' => $role->id, 'member_id' => $bruno->id]);
        foreach ($before as $table => $rows) { $this->assertSame($rows, DB::table($table)->orderBy('id')->get()->toJson()); }
        $this->assertSoftDeleted('members', ['id' => $bruno->id]);
    }

    public function test_multiple_people_can_hold_the_same_role_and_the_same_person_can_hold_other_roles(): void
    {
        $department = $this->department();
        $ana = $this->member('Ana'); $bruno = $this->member('Bruno');
        $this->actingAs($this->user());
        $this->post(route('departments.administration.store', $department), ['name' => 'Secretaria', 'responsible_member_ids' => [$ana->id, $bruno->id]])->assertRedirect();
        $role = $department->roles()->sole();
        $this->assertEqualsCanonicalizing([$ana->id,$bruno->id], $role->assigned_members->pluck('id')->all());
        $this->assertSame(2, $department->members()->count());
        $this->post(route('departments.administration.store', $department), ['name' => 'Tesouraria', 'responsible_member_ids' => [$ana->id, $bruno->id]])->assertRedirect();
        $this->assertDatabaseCount('department_role_assignments', 4);
        $this->get(route('departments.administration.index', $department))->assertOk()->assertSee('name="responsible_member_ids[]"', false)->assertSee('Adicionar ou ajustar pessoas')->assertSee('Ana')->assertSee('Bruno');
        $html = $this->get(route('departments.show', $department))->assertOk()->getContent();
        $document = new \DOMDocument; @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $row = (new \DOMXPath($document))->query('//*[@id="department-member-'.$bruno->id.'"]')->item(0);
        $this->assertStringContainsString('Secretaria', $row->textContent);
        $this->assertStringContainsString('Tesouraria', $row->textContent);
    }

    public function test_adding_keeps_old_assignments_and_removing_only_affects_the_requested_role(): void
    {
        $department = $this->department();
        $ana = $this->member('Ana'); $bruno = $this->member('Bruno'); $carla = $this->member('Carla');
        $role = $department->roles()->create(['name'=>'Secretaria', 'responsible_member_id'=>$ana->id]);
        $other = $department->roles()->create(['name'=>'Mídia']);
        $department->members()->attach($bruno->id, ['department_role_id'=>$role->id]);
        $department->members()->attach($carla->id, ['department_role_id'=>$other->id]);
        $this->actingAs($this->user());
        $url = route('departments.administration.update', [$department, $role]);
        $this->put($url, ['name'=>'Secretaria', 'responsible_member_ids'=>[$ana->id,$bruno->id,$carla->id]])->assertRedirect();
        $this->assertDatabaseHas('department_members', ['member_id'=>$bruno->id, 'department_role_id'=>$role->id]);
        $this->assertDatabaseHas('department_members', ['member_id'=>$carla->id, 'department_role_id'=>$other->id]);
        $this->put($url, ['name'=>'Secretaria', 'responsible_member_ids'=>[$ana->id,$carla->id]])->assertRedirect();
        $this->assertEqualsCanonicalizing([$ana->id,$carla->id], $role->fresh()->assigned_members->pluck('id')->all());
        $this->assertDatabaseHas('department_members', ['member_id'=>$bruno->id, 'department_role_id'=>null]);
        $this->assertDatabaseHas('department_members', ['member_id'=>$carla->id, 'department_role_id'=>$other->id]);
        $this->assertDatabaseCount('department_members', 3);
    }

    public function test_clearing_assignments_keeps_department_membership(): void
    {
        $department = $this->department(); $ana = $this->member('Ana');
        $role = $department->roles()->create(['name'=>'Secretaria', 'responsible_member_id'=>$ana->id]);
        $role->responsibleMembers()->attach($ana->id);
        $department->members()->attach($ana->id, ['department_role_id'=>$role->id]);
        $this->actingAs($this->user())->put(route('departments.administration.update', [$department,$role]), ['name'=>'Secretaria', 'responsibles_present'=>'1'])->assertRedirect();
        $this->assertTrue($role->fresh()->assigned_members->isEmpty());
        $this->assertNull($role->fresh()->responsible_member_id);
        $this->assertDatabaseHas('department_members', ['member_id'=>$ana->id, 'department_id'=>$department->id, 'department_role_id'=>null]);
    }

    public function test_settings_preserve_every_person_assigned_to_a_role_not_only_the_first(): void
    {
        $department = $this->department(); $ana = $this->member('Ana'); $bruno = $this->member('Bruno');
        $this->actingAs($this->user());
        $this->post(route('departments.administration.store', $department), ['name'=>'Cargo', 'responsible_member_ids'=>[$ana->id,$bruno->id]])->assertRedirect();
        $this->put(route('departments.update', $department), ['name'=>'Departamento revisado', 'status'=>'ativo', 'members'=>[]])->assertSessionHasNoErrors();
        $this->assertEqualsCanonicalizing([$ana->id,$bruno->id], $department->members()->pluck('members.id')->all());
        $html = $this->get(route('departments.edit', $department))->assertOk()->getContent();
        $document = new \DOMDocument; @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $this->assertSame(2, (new \DOMXPath($document))->query('//input[@name="members[]" and @checked and @disabled]')->length);
    }

    public function test_invalid_duplicate_deleted_or_unauthorized_assignments_are_rejected(): void
    {
        $department = $this->department(); $other = $this->department('Outro');
        $ana = $this->member('Ana'); $deleted = $this->member('Excluído'); $deleted->delete();
        $role = $department->roles()->create(['name'=>'Cargo']);
        $this->actingAs($this->user());
        foreach ([[$ana->id,$ana->id],[$deleted->id],[99999]] as $ids) {
            $this->putJson(route('departments.administration.update', [$department,$role]), ['name'=>'Cargo','responsible_member_ids'=>$ids])->assertUnprocessable();
        }
        $this->putJson(route('departments.administration.update', [$other,$role]), ['name'=>'Cargo','responsible_member_ids'=>[$ana->id]])->assertNotFound();
        $this->actingAs($this->user(false));
        $this->putJson(route('departments.administration.update', [$department,$role]), ['name'=>'Cargo','responsible_member_ids'=>[$ana->id]])->assertForbidden();
        $this->assertDatabaseCount('department_role_assignments',0);
    }

    public function test_department_styles_are_in_the_head_once_on_every_department_page(): void
    {
        $department = $this->department();
        $project = $department->projects()->create(['name'=>'Projeto']);
        $action = $project->actions()->create(['name'=>'Ação']);
        $this->actingAs($this->user());
        $routes = [
            route('departments.index'),route('departments.create'),route('departments.show',$department),route('departments.edit',$department),
            route('departments.administration.index',$department),route('departments.events.index',$department),
            route('departments.projects.index',$department),route('departments.projects.create',$department),route('departments.projects.show',[$department,$project]),
            route('departments.projects.actions.edit',[$department,$project,$action]),
        ];
        foreach ($routes as $url) {
            $html = $this->get($url)->assertOk()->getContent();
            $document = new \DOMDocument; @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
            $this->assertSame(1,(new \DOMXPath($document))->query('//head/link[contains(@href,"/css/css/departments.css?v=")]')->length, $url);
            $this->assertSame(1,substr_count($html,'css/css/departments.css?v='),$url);
        }
    }
}
