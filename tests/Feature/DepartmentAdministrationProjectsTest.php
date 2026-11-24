<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\DepartmentProject;
use App\Models\DepartmentRole;
use App\Models\Member;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DepartmentAdministrationProjectsTest extends TestCase
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
            $table->id(); $table->string('name'); $table->string('status')->default('ativo'); $table->timestamps(); $table->softDeletes();
        });
        (require database_path('migrations/2023_12_31_000001_create_departments_table.php'))->up();
        (require database_path('migrations/2024_01_02_000001_add_fields_to_departments_table.php'))->up();
        Schema::table('departments', function (Blueprint $table) {
            $table->string('logo_url')->nullable(); $table->string('banner_url')->nullable();
            $table->boolean('show_on_homepage')->default(false); $table->integer('homepage_order')->nullable(); $table->string('homepage_url')->nullable();
        });
        foreach (['2024_01_02_000002_create_department_members_table.php', '2024_01_02_000003_create_department_roles_table.php', '2024_01_02_000004_add_foreign_key_department_role_to_department_members.php', '2026_11_23_130000_add_responsible_member_to_department_roles.php', '2026_11_23_140000_create_department_projects_tables.php'] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        Schema::create('department_leaders', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('department_id'); $table->unsignedBigInteger('member_id'); $table->timestamps();
        });
        Schema::create('events', function (Blueprint $table) {
            $table->id(); $table->dateTime('start_date'); $table->dateTime('end_date')->nullable();
        });
        (require database_path('migrations/2026_11_24_100000_create_department_role_assignments_table.php'))->up();
    }

    private function user(array $permissions = [], bool $admin = true): User
    {
        $user = User::create(['name' => 'Teste', 'email' => uniqid().'@example.test', 'password' => 'test', 'is_admin' => $admin]);
        $user->setRelation('permissions', collect($permissions)->map(fn ($key) => new Permission(['key' => $key])));
        $user->setRelation('member', null);

        return $user;
    }

    private function department(string $name = 'Departamento de teste'): Department
    {
        return Department::create(['name' => $name, 'status' => 'ativo']);
    }

    private function member(string $name = 'Pessoa do cadastro geral'): Member
    {
        return Member::create(['name' => $name]);
    }

    public function test_responsible_is_chosen_from_all_members_and_preserves_existing_assignments(): void
    {
        $department = $this->department();
        $legacy = $this->member('Participante anteriormente atribuído');
        $chosen = $this->member();
        $role = $department->roles()->create(['name' => 'Secretaria']);
        $otherRole = $department->roles()->create(['name' => 'Outro cargo']);
        $department->members()->attach($legacy->id, ['department_role_id' => $role->id]);
        $department->members()->attach($chosen->id, ['department_role_id' => $otherRole->id]);
        $outside = $this->member('Pessoa fora do departamento');
        $beforeLegacy = DB::table('department_members')->where('member_id', $legacy->id)->first();
        $this->actingAs($this->user(['servico.departments.edit'], false));
        $this->put(route('departments.administration.update', [$department, $role]), ['name' => 'Secretaria', 'responsible_member_id' => $chosen->id])->assertRedirect();
        $this->assertDatabaseHas('department_roles', ['id' => $role->id, 'responsible_member_id' => $chosen->id]);
        $this->assertDatabaseHas('department_members', ['member_id' => $chosen->id, 'department_role_id' => $otherRole->id]);
        $this->assertEquals($beforeLegacy, DB::table('department_members')->where('member_id', $legacy->id)->first());
        $this->put(route('departments.administration.update', [$department, $role]), ['name' => 'Secretaria', 'responsible_member_id' => $outside->id])->assertRedirect();
        $this->assertDatabaseHas('department_members', ['department_id' => $department->id, 'member_id' => $outside->id]);
        $this->assertDatabaseHas('department_members', ['member_id' => $chosen->id, 'department_role_id' => $otherRole->id]);
        $this->post(route('departments.administration.store', $department), ['name' => 'Tesouraria', 'responsible_member_id' => $outside->id])->assertRedirect();
        $this->assertSame(2, $department->roles()->where('responsible_member_id', $outside->id)->count());
        $this->actingAs($this->user());
        $this->get(route('departments.administration.index', $department))->assertOk()->assertSee('Participante anteriormente atribuído')->assertSee('Pessoa fora do departamento')->assertSee('data-people-picker', false);
        $html = $this->get(route('departments.show', $department))->assertOk()->getContent();
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($document);
        $row = $xpath->query('//*[@id="department-member-'.$outside->id.'"]')->item(0);
        $this->assertNotNull($row);
        $this->assertStringContainsString('Secretaria', $row->textContent);
        $this->assertStringContainsString('Tesouraria', $row->textContent);
    }

    public function test_general_department_edit_and_removing_role_do_not_erase_responsible_or_participants(): void
    {
        $department = $this->department();
        $member = $this->member();
        $role = $department->roles()->create(['name' => 'Secretaria', 'responsible_member_id' => $member->id]);
        $department->members()->attach($member->id, ['department_role_id' => $role->id]);
        $this->actingAs($this->user());
        $this->put(route('departments.update', $department), ['name' => $department->name, 'status' => 'ativo', 'members' => []])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('department_members', ['department_id' => $department->id, 'member_id' => $member->id, 'department_role_id' => $role->id]);
        $this->assertSame($member->id, (int) $role->fresh()->responsible_member_id);
        $this->delete(route('departments.administration.destroy', [$department, $role]))->assertRedirect();
        $this->assertSoftDeleted('department_roles', ['id' => $role->id]);
        $this->assertDatabaseHas('department_members', ['department_id' => $department->id, 'member_id' => $member->id]);
    }

    public function test_administration_rejects_invalid_or_deleted_members_and_cross_department_roles(): void
    {
        $owner = $this->department();
        $other = $this->department('Outro');
        $role = $owner->roles()->create(['name' => 'Mídia']);
        $member = $this->member(); $member->delete();
        $this->actingAs($this->user());
        foreach ([$member->id, 99999] as $id) {
            $this->postJson(route('departments.administration.store', $owner), ['name' => 'Mídia', 'responsible_member_id' => $id])->assertUnprocessable()->assertJsonValidationErrors('responsible_member_id');
            $this->putJson(route('departments.administration.update', [$owner, $role]), ['name' => 'Mídia', 'responsible_member_id' => $id])->assertUnprocessable();
        }
        $this->putJson(route('departments.administration.update', [$other, $role]), ['name' => 'Inválido'])->assertNotFound();
        $this->deleteJson(route('departments.administration.destroy', [$other, $role]))->assertNotFound();
        $default = $owner->roles()->create(['name' => 'Líder', 'is_default' => true]);
        $this->delete(route('departments.administration.destroy', [$owner, $default]))->assertSessionHas('error');
        $this->assertNotSoftDeleted('department_roles', ['id' => $default->id]);
        $role->update(['responsible_member_id' => $member->id]);
        $this->get(route('departments.administration.index', $owner))->assertOk()->assertSee('Membro excluído ou indisponível');
        $this->put(route('departments.administration.update', [$owner, $role]), ['name' => 'Mídia', 'responsible_member_id' => null])->assertRedirect();
        $this->assertNull($role->fresh()->responsible_member_id);
        $this->assertSame(1, Member::onlyTrashed()->count());
    }

    public function test_administration_uses_existing_edit_permission_and_readers_do_not_get_global_member_choices(): void
    {
        $department = $this->department();
        $role = $department->roles()->create(['name' => 'Secretaria']);
        $this->member('Pessoa externa não vinculada');
        $this->get(route('departments.administration.index', $department))->assertRedirect(route('login'));
        $this->actingAs($this->user(['servico.departments.view'], false));
        $this->get(route('departments.administration.index', $department))->assertOk()->assertDontSee('Cadastrar cargo')->assertDontSee('Pessoa externa não vinculada');
        $this->postJson(route('departments.administration.store', $department), ['name' => 'Cargo'])->assertForbidden();
        $this->putJson(route('departments.administration.update', [$department, $role]), ['name' => 'Cargo'])->assertForbidden();
        $this->deleteJson(route('departments.administration.destroy', [$department, $role]))->assertForbidden();
        $this->actingAs($this->user(['servico.departments.create'], false));
        $this->postJson(route('departments.administration.store', $department), ['name' => 'Cargo'])->assertForbidden();
        $this->actingAs($this->user(['servico.departments.edit'], false));
        $this->post(route('departments.administration.store', $department), ['name' => 'Cargo permitido'])->assertRedirect();
    }

    public function test_complete_project_and_action_flow_and_cascade_deletion(): void
    {
        $department = $this->department();
        $this->actingAs($this->user());
        $this->get(route('departments.projects.index', $department))->assertOk()->assertSee('Nenhum projeto');
        $this->get(route('departments.projects.create', $department))->assertOk();
        $this->post(route('departments.projects.store', $department), ['name' => 'Projeto de teste', 'description' => 'Propósito do projeto'])->assertRedirect();
        $project = $department->projects()->sole();
        $this->get(route('departments.projects.edit', [$department, $project]))->assertOk();
        $this->put(route('departments.projects.update', [$department, $project]), ['name' => 'Projeto revisado', 'description' => 'Nova descrição'])->assertRedirect();
        $this->get(route('departments.projects.actions.create', [$department, $project]))->assertOk();
        $this->post(route('departments.projects.actions.store', [$department, $project]), ['name' => 'Ação de teste', 'details' => 'Detalhes da iniciativa'])->assertRedirect();
        $action = $project->actions()->sole();
        $this->get(route('departments.projects.show', [$department, $project]))->assertOk()->assertSee('Projeto revisado')->assertSee('Detalhes da iniciativa');
        $this->get(route('departments.projects.actions.edit', [$department, $project, $action]))->assertOk();
        $this->put(route('departments.projects.actions.update', [$department, $project, $action]), ['name' => 'Ação revisada', 'details' => 'Novos detalhes'])->assertRedirect();
        $this->assertDatabaseHas('department_project_actions', ['id' => $action->id, 'name' => 'Ação revisada']);
        $this->delete(route('departments.projects.actions.destroy', [$department, $project, $action]))->assertRedirect();
        $this->assertDatabaseMissing('department_project_actions', ['id' => $action->id]);
        $child = $project->actions()->create(['name' => 'Outra ação']);
        $this->delete(route('departments.projects.destroy', [$department, $project]))->assertRedirect();
        $this->assertDatabaseMissing('department_projects', ['id' => $project->id]);
        $this->assertDatabaseMissing('department_project_actions', ['id' => $child->id]);
        $this->assertDatabaseHas('departments', ['id' => $department->id, 'deleted_at' => null]);
    }

    public function test_nested_projects_and_actions_cannot_be_accessed_through_another_department_or_project(): void
    {
        $owner = $this->department();
        $other = $this->department('Outro');
        $project = $owner->projects()->create(['name' => 'Projeto restrito']);
        $another = $owner->projects()->create(['name' => 'Segundo projeto']);
        $action = $project->actions()->create(['name' => 'Ação restrita']);
        $this->actingAs($this->user());
        $this->get(route('departments.projects.show', [$other, $project]))->assertNotFound();
        $this->get(route('departments.projects.edit', [$other, $project]))->assertNotFound();
        $this->putJson(route('departments.projects.update', [$other, $project]), ['name' => 'Inválido'])->assertNotFound();
        $this->deleteJson(route('departments.projects.destroy', [$other, $project]))->assertNotFound();
        $this->postJson(route('departments.projects.actions.store', [$other, $project]), ['name' => 'Inválido'])->assertNotFound();
        foreach ([[$other, $project], [$owner, $another]] as [$department, $parent]) {
            $this->get(route('departments.projects.actions.edit', [$department, $parent, $action]))->assertNotFound();
            $this->putJson(route('departments.projects.actions.update', [$department, $parent, $action]), ['name' => 'Inválido'])->assertNotFound();
            $this->deleteJson(route('departments.projects.actions.destroy', [$department, $parent, $action]))->assertNotFound();
        }
        $owner->delete();
        $this->get(route('departments.projects.show', [$owner, $project]))->assertNotFound();
        $this->assertDatabaseHas('department_projects', ['id' => $project->id]);
        $this->assertDatabaseHas('department_project_actions', ['id' => $action->id]);
    }

    public function test_projects_and_actions_follow_granular_department_permissions_and_validate_input(): void
    {
        $department = $this->department();
        $project = $department->projects()->create(['name' => 'Projeto']);
        $action = $project->actions()->create(['name' => 'Ação']);
        $this->actingAs($this->user(['servico.departments.view'], false));
        $this->get(route('departments.projects.index', $department))->assertOk()->assertDontSee('Novo projeto');
        $this->get(route('departments.projects.show', [$department, $project]))->assertOk()->assertDontSee('Editar projeto')->assertDontSee('Excluir ação');
        $this->postJson(route('departments.projects.store', $department), ['name' => 'Proibido'])->assertForbidden();
        $this->postJson(route('departments.projects.actions.store', [$department, $project]), ['name' => 'Proibido'])->assertForbidden();
        $this->putJson(route('departments.projects.actions.update', [$department, $project, $action]), ['name' => 'Proibido'])->assertForbidden();
        $this->deleteJson(route('departments.projects.destroy', [$department, $project]))->assertForbidden();
        $this->actingAs($this->user(['servico.departments.create'], false));
        $this->postJson(route('departments.projects.store', $department), ['name' => ''])->assertUnprocessable()->assertJsonValidationErrors('name');
        $this->postJson(route('departments.projects.actions.store', [$department, $project]), ['name' => str_repeat('a', 192)])->assertUnprocessable();
        $this->post(route('departments.projects.store', $department), ['name' => 'Permitido'])->assertRedirect();
        $this->actingAs($this->user(['servico.departments.edit'], false));
        $this->put(route('departments.projects.update', [$department, $project]), ['name' => 'Revisado'])->assertRedirect();
        $this->deleteJson(route('departments.projects.actions.destroy', [$department, $project, $action]))->assertForbidden();
        $this->actingAs($this->user(['servico.departments.delete'], false));
        $this->delete(route('departments.projects.actions.destroy', [$department, $project, $action]))->assertRedirect();
    }

    public function test_migrations_can_be_rolled_back_and_do_not_seed_example_data(): void
    {
        $department = $this->department();
        $role = $department->roles()->create(['name' => 'Cargo anterior']);
        $this->assertDatabaseCount('department_projects', 0);
        $this->assertDatabaseCount('department_project_actions', 0);
        (require database_path('migrations/2026_11_23_140000_create_department_projects_tables.php'))->down();
        (require database_path('migrations/2026_11_23_130000_add_responsible_member_to_department_roles.php'))->down();
        $this->assertFalse(Schema::hasTable('department_projects'));
        $this->assertFalse(Schema::hasColumn('department_roles', 'responsible_member_id'));
        $this->assertDatabaseHas('department_roles', ['id' => $role->id, 'name' => 'Cargo anterior']);
        $this->assertDatabaseHas('departments', ['id' => $department->id]);
    }
}
