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

class DepartmentUiTest extends TestCase
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
        foreach (['2023_12_31_000001_create_departments_table.php', '2024_01_02_000001_add_fields_to_departments_table.php', '2024_01_02_000002_create_department_members_table.php', '2024_01_02_000003_create_department_roles_table.php', '2024_01_02_000004_add_foreign_key_department_role_to_department_members.php', '2026_01_14_145533_create_department_leaders_table.php', '2026_11_23_130000_add_responsible_member_to_department_roles.php'] as $file) {
            (require database_path('migrations/'.$file))->up();
        }
        Schema::table('departments', function (Blueprint $table) {
            $table->string('logo_url')->nullable(); $table->string('banner_url')->nullable(); $table->boolean('show_on_homepage')->default(false);
            $table->integer('homepage_order')->nullable(); $table->string('homepage_url')->nullable();
        });
        Schema::create('events', function (Blueprint $table) {
            $table->id(); $table->dateTime('start_date'); $table->dateTime('end_date')->nullable();
        });
        (require database_path('migrations/2026_11_24_100000_create_department_role_assignments_table.php'))->up();
    }

    private function user(bool $admin = true): User
    {
        $user = User::create(['name' => 'Teste', 'email' => uniqid().'@example.test', 'password' => 'test', 'is_admin' => $admin]);
        $user->setRelation('permissions', collect($admin ? [] : [new Permission(['key' => 'servico.departments.view'])]));
        $user->setRelation('member', null);

        return $user;
    }

    private function department(array $data = []): Department
    {
        return Department::create(array_merge(['name' => 'Equipe de teste', 'status' => 'ativo', 'description' => 'Propósito da equipe', 'icon' => 'bx-group', 'color' => '#3458bd'], $data));
    }

    private function member(string $name = 'Ana & José <Equipe>'): Member
    {
        return Member::create(['name' => $name]);
    }

    private function xpath(string $html): \DOMXPath
    {
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);

        return new \DOMXPath($document);
    }

    public function test_edit_has_clear_names_native_people_controls_and_no_role_creation_fields(): void
    {
        $department = $this->department();
        $member = $this->member();
        $department->members()->attach($member->id);
        $department->roles()->create(['name' => 'Secretaria', 'responsible_member_id' => $member->id]);
        $this->actingAs($this->user());
        $response = $this->get(route('departments.edit', $department))->assertOk()
            ->assertSee('Adicionar ou ajustar participantes')->assertSee('Administração')->assertSee('Salvar alterações')
            ->assertDontSee('addRoleBtn')->assertDontSee('roles_to_delete')->assertDontSee('name="roles[', false)
            ->assertDontSee('Segure Ctrl')->assertSee('data-person-name="Ana &amp; José &lt;Equipe&gt;"', false);
        $xpath = $this->xpath($response->getContent());
        $checkbox = $xpath->query('//input[@name="members[]" and @value="'.$member->id.'" and @checked and @disabled]');
        $this->assertSame(1, $checkbox->length);
        $this->assertStringContainsString('/js/departments.js?v=', $response->getContent());
        $this->assertSame(1, substr_count($response->getContent(), 'css/css/departments.css?v='));
    }

    public function test_settings_save_preserves_roles_responsible_members_and_hidden_historical_links(): void
    {
        $department = $this->department(['template' => 'louvor']);
        $member = $this->member('Participante atual');
        $owner = $this->member('Responsável');
        $removed = $this->member('Membro excluído');
        $new = $this->member('Pessoa nova');
        $role = $department->roles()->create(['name' => 'Secretaria', 'responsible_member_id' => $owner->id]);
        foreach ([$member, $owner, $removed] as $person) { $department->members()->attach($person->id, ['department_role_id' => $role->id]); }
        $department->leaders()->attach([$member->id, $removed->id]);
        $department->update(['leader_id' => $member->id]);
        $removed->delete();
        $roleBefore = $role->fresh()->getAttributes();
        $this->actingAs($this->user())->put(route('departments.update', $department), [
            'name' => 'Equipe revisada', 'status' => 'ativo', 'description' => 'Nova apresentação',
            'icon' => 'bx-globe', 'color' => '#8955aa', 'members' => [$member->id, $new->id], 'leaders_present' => '1',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame($roleBefore, $role->fresh()->getAttributes());
        foreach ([$member, $owner, $removed, $new] as $person) {
            $this->assertDatabaseHas('department_members', ['department_id' => $department->id, 'member_id' => $person->id]);
        }
        $this->assertDatabaseHas('department_members', ['member_id' => $owner->id, 'department_role_id' => $role->id]);
        $this->assertDatabaseHas('department_leaders', ['department_id' => $department->id, 'member_id' => $removed->id]);
        $this->assertDatabaseMissing('department_leaders', ['department_id' => $department->id, 'member_id' => $member->id]);
        $this->assertNull($department->fresh()->leader_id);
        $this->assertSame('#8955aa', $department->fresh()->color);
        $this->assertSame('bx-globe', $department->fresh()->icon);
        $this->assertSame('louvor', $department->fresh()->template);
        $this->assertSoftDeleted('members', ['id' => $removed->id]);
    }

    public function test_leadership_can_be_cleared_and_omitted_leadership_is_preserved(): void
    {
        $member = $this->member();
        $department = $this->department(['leader_id' => $member->id]);
        $this->actingAs($this->user());
        $this->put(route('departments.update', $department), ['name' => 'Nome revisado', 'status' => 'ativo'])->assertSessionHasNoErrors();
        $this->assertSame($member->id, (int) $department->fresh()->leader_id);
        $this->put(route('departments.update', $department), ['name' => 'Nome revisado', 'status' => 'ativo', 'leaders_present' => '1'])->assertSessionHasNoErrors();
        $this->assertNull($department->fresh()->leader_id);
    }

    public function test_validation_keeps_the_person_selections_and_names_on_retry(): void
    {
        $department = $this->department();
        $old = $this->member('Participante anterior');
        $new = $this->member('Seleção a manter');
        $department->members()->attach($old->id);
        $this->actingAs($this->user());
        $this->from(route('departments.edit', $department))->put(route('departments.update', $department), [
            'name' => '', 'status' => 'ativo', 'members' => [$new->id], 'leaders_present' => '1', 'leaders' => [$new->id],
        ])->assertSessionHasErrors('name');
        $html = $this->get(route('departments.edit', $department))->assertOk()->getContent();
        $xpath = $this->xpath($html);
        $this->assertSame(1, $xpath->query('//input[@name="members[]" and @value="'.$new->id.'" and @checked]')->length);
        $this->assertSame(0, $xpath->query('//input[@name="members[]" and @value="'.$old->id.'" and @checked]')->length);
        $this->assertSame(1, $xpath->query('//input[@name="leaders[]" and @value="'.$new->id.'" and @checked]')->length);
        $this->assertDatabaseHas('department_members', ['department_id' => $department->id, 'member_id' => $old->id]);
        $this->assertDatabaseMissing('department_members', ['department_id' => $department->id, 'member_id' => $new->id]);
    }

    public function test_catalog_search_stays_within_the_status_filter_and_preserves_pagination_query(): void
    {
        for ($index = 0; $index < 14; $index++) { $this->department(['name' => 'Equipe '.str_pad((string) $index, 2, '0', STR_PAD_LEFT)]); }
        $this->department(['name' => 'Equipe arquivada', 'status' => 'arquivado']);
        $this->department(['name' => 'Outro grupo', 'description' => 'Fora da busca']);
        $this->actingAs($this->user());
        $this->get(route('departments.index', ['q' => 'Equipe', 'filter' => 'ativo']))->assertOk()
            ->assertViewHas('departments', fn ($rows) => $rows->total() === 14 && $rows->count() === 12)
            ->assertSee('q=Equipe', false)->assertDontSee('Equipe arquivada')->assertDontSee('Outro grupo');
        $this->get(route('departments.index', ['q' => 'Equipe', 'filter' => 'arquivado']))->assertOk()->assertSee('Equipe arquivada');
    }

    public function test_creation_uses_the_same_simple_people_form_without_creating_extra_roles(): void
    {
        $member = $this->member();
        $this->actingAs($this->user());
        $this->get(route('departments.create'))->assertOk()->assertSee('Criar departamento')->assertDontSee('addRoleBtn');
        $this->post(route('departments.store'), ['name' => 'Nova equipe', 'status' => 'ativo', 'members' => [$member->id], 'leaders' => [$member->id]])->assertSessionHasNoErrors();
        $department = Department::where('name', 'Nova equipe')->sole();
        $this->assertSame([$member->id], $department->members->modelKeys());
        $this->assertSame([$member->id], $department->leaders->modelKeys());
        $this->assertSame(1, $department->roles()->count());
    }

    public function test_readers_can_view_but_cannot_edit_or_see_edit_actions(): void
    {
        $department = $this->department();
        $this->actingAs($this->user(false));
        $this->get(route('departments.show', $department))->assertOk()->assertDontSee('Editar departamento')->assertSee('Projetos e iniciativas');
        $this->getJson(route('departments.edit', $department))->assertForbidden();
        $this->putJson(route('departments.update', $department), ['name' => 'Proibido', 'status' => 'ativo'])->assertForbidden();
    }
}
