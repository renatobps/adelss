<?php

namespace Tests\Feature;

use App\Models\Cartela;
use App\Models\Department;
use App\Models\Member;
use App\Models\Permission;
use App\Models\Rifa;
use App\Models\User;
use App\Services\RifaService;
use App\Services\VendaRifaService;
use App\Services\WhatsAppService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RifaDistributionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        Schema::create('users', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('email'); $t->string('password');
            $t->boolean('is_admin')->default(false); $t->unsignedBigInteger('member_id')->nullable(); $t->timestamps();
        });
        Schema::create('departments', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('status')->default('ativo'); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('members', function (Blueprint $t) {
            $t->id(); $t->string('name'); $t->string('phone')->nullable(); $t->string('status')->default('ativo');
            $t->unsignedBigInteger('department_id')->nullable(); $t->timestamps(); $t->softDeletes();
        });
        Schema::create('events', function (Blueprint $t) {
            $t->id(); $t->dateTime('start_date'); $t->dateTime('end_date')->nullable();
        });
        Schema::create('department_members', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('member_id'); $t->unsignedBigInteger('department_id');
            $t->unsignedBigInteger('department_role_id')->nullable(); $t->timestamps();
        });
        foreach ([
            '2026_04_22_112744_create_rifas_table.php', '2026_04_22_112745_create_numeros_rifa_table.php',
            '2026_04_22_112745_create_rifa_vendas_table.php', '2026_04_22_112746_create_cartelas_table.php',
            '2026_04_22_112747_create_cartela_numero_table.php', '2026_11_14_130000_add_department_and_seller_to_rifas.php',
            '2026_11_14_140000_create_rifa_vendedores_table.php',
        ] as $migration) (require database_path('migrations/'.$migration))->up();
        Http::preventStrayRequests();
    }

    private function admin(): User
    {
        return User::create(['name' => 'Admin', 'email' => 'admin@example.test', 'password' => Hash::make('password'), 'is_admin' => true]);
    }

    private function department(): Department
    {
        return Department::create(['name' => 'Jovens', 'status' => 'ativo']);
    }

    private function member(?Department $department, string $name, array $attributes = []): Member
    {
        return Member::create(array_merge(['name' => $name, 'status' => 'ativo', 'phone' => '(61) 99999-0000', 'department_id' => $department?->id], $attributes));
    }

    private function rifa(?Department $department = null, array $attributes = []): Rifa
    {
        return app(RifaService::class)->criarComNumerosECartelas(array_merge([
            'nome' => 'Rifa da Juventude', 'quantidade_numeros' => 23, 'numeros_por_cartela' => 5,
            'valor_numero' => 10, 'status' => 'ativa', 'department_id' => $department?->id,
        ], $attributes));
    }

    public function test_cartelas_are_balanced_between_active_department_members_without_duplicates(): void
    {
        $department = $this->department();
        $ana = $this->member($department, 'Ana');
        $department->members()->attach($ana); // Same member through both kinds of membership.
        $bia = $this->member(null, 'Bia'); $department->members()->attach($bia);
        $this->member($department, 'Inativo', ['status' => 'inativo']);
        $deleted = $this->member($department, 'Excluído'); $deleted->delete();
        $this->member(null, 'Outro departamento');
        $rifa = $this->rifa($department);
        $counts = $rifa->cartelas()->get()->countBy('vendedor_id');
        $this->assertSame(3, $counts[$ana->id]); $this->assertSame(2, $counts[$bia->id]);
        $this->assertCount(2, $counts);
        $this->assertSame(23, $rifa->numeros()->where('status', 'disponivel')->count());
        $this->assertSame(0, $rifa->numeros()->whereNotNull('vendedor_id')->count());
        $this->assertSame(23, DB::table('cartela_numero')->distinct()->count('numero_id'));
        $this->assertSame(0, app(RifaService::class)->distribuirCartelas($rifa));
        Http::assertNothingSent();
    }

    public function test_creation_rolls_back_when_selected_department_has_no_active_members(): void
    {
        $this->actingAs($this->admin())->post(route('rifas.store'), [
            'nome' => 'Sem vendedores', 'quantidade_numeros' => 10, 'numeros_por_cartela' => 5,
            'valor_numero' => 10, 'status' => 'ativa', 'department_id' => $this->department()->id,
        ])->assertSessionHasErrors('department_id');
        $this->assertSame(0, Rifa::count()); $this->assertSame(0, Cartela::count());
        $this->assertSame(0, DB::table('numeros_rifa')->count());
    }

    public function test_creation_without_department_preserves_legacy_behavior(): void
    {
        $rifa = $this->rifa();
        $this->assertSame(5, $rifa->cartelas()->whereNull('vendedor_id')->count());
        $this->assertSame(23, $rifa->numeros()->count());
    }

    public function test_individual_members_receive_cards_without_a_department_and_new_numbers_use_same_roster(): void
    {
        $ana = $this->member(null, 'Ana'); $bia = $this->member(null, 'Bia'); $this->member(null, 'Não selecionado');
        $rifa = $this->rifa(null, ['vendedor_ids' => [$ana->id, $bia->id]]);
        $this->assertNull($rifa->department_id);
        $this->assertSame([$ana->id, $bia->id], $rifa->membrosVendedores(false)->pluck('members.id')->all());
        $counts = $rifa->cartelas()->get()->countBy('vendedor_id');
        $this->assertSame(3, $counts[$ana->id]); $this->assertSame(2, $counts[$bia->id]);
        app(RifaService::class)->adicionarNumeros($rifa, 5);
        $this->assertSame(3, $rifa->cartelas()->where('vendedor_id', $bia->id)->count());
        $this->assertSame(0, $rifa->cartelas()->whereNull('vendedor_id')->count());
        Http::assertNothingSent();
    }

    public function test_department_and_individual_member_lists_are_combined_without_duplicate_sellers(): void
    {
        $department = $this->department(); $ana = $this->member($department, 'Ana'); $bia = $this->member(null, 'Bia');
        $rifa = $this->rifa($department, ['vendedor_ids' => [$ana->id, $bia->id]]);
        $this->assertSame([$ana->id, $bia->id], $rifa->membrosVendedores(false)->pluck('members.id')->all());
        $this->assertSame(2, $rifa->cartelas()->distinct()->count('vendedor_id'));
        $this->assertSame(5, $rifa->cartelas()->count());
    }

    public function test_create_route_validates_and_persists_manual_sellers(): void
    {
        $ana = $this->member(null, 'Ana');
        $this->actingAs($this->admin())->post(route('rifas.store'), [
            'nome' => 'Rifa manual', 'quantidade_numeros' => 10, 'numeros_por_cartela' => 5,
            'valor_numero' => 10, 'status' => 'ativa', 'vendedor_ids' => [$ana->id], 'atualizar_vendedores' => 1,
        ])->assertSessionHasNoErrors()->assertRedirect(route('rifas.show', Rifa::firstOrFail()));
        $rifa = Rifa::firstOrFail();
        $this->assertSame(1, $rifa->vendedoresSelecionados()->count());
        $this->assertSame(2, $rifa->cartelas()->where('vendedor_id', $ana->id)->count());
        $this->get(route('rifas.edit', $rifa))->assertOk()->assertSee('Adicionar vendedores da lista de membros')
            ->assertSee('id="rifa-member-'.$ana->id.'" value="'.$ana->id.'" checked', false);
    }

    public function test_manual_seller_selection_rejects_inactive_deleted_and_duplicate_ids_on_create(): void
    {
        $inactive = $this->member(null, 'Inativo', ['status' => 'inativo']);
        $deleted = $this->member(null, 'Excluído'); $deleted->delete(); $active = $this->member(null, 'Ativo');
        $this->actingAs($this->admin());
        foreach ([[$inactive->id], [$deleted->id], [$active->id, $active->id]] as $ids) {
            $this->post(route('rifas.store'), [
                'nome' => 'Inválida', 'quantidade_numeros' => 10, 'numeros_por_cartela' => 5,
                'valor_numero' => 10, 'status' => 'ativa', 'vendedor_ids' => $ids,
            ])->assertSessionHasErrors();
        }
        $this->assertSame(0, Rifa::count());
    }

    public function test_edit_adds_members_for_future_distribution_without_reassigning_existing_cards(): void
    {
        $ana = $this->member(null, 'Ana'); $bia = $this->member(null, 'Bia');
        $rifa = $this->rifa(null, ['vendedor_ids' => [$ana->id]]);
        $previous = $rifa->cartelas()->pluck('vendedor_id', 'id')->all();
        $this->actingAs($this->admin())->put(route('rifas.update', $rifa), [
            'nome' => $rifa->nome, 'valor_numero' => 10, 'status' => 'ativa', 'atualizar_vendedores' => 1,
            'vendedor_ids' => [$ana->id, $bia->id],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame($previous, $rifa->cartelas()->pluck('vendedor_id', 'id')->all());
        app(RifaService::class)->adicionarNumeros($rifa, 5);
        $this->assertSame($bia->id, $rifa->cartelas()->latest('id')->first()->vendedor_id);
    }

    public function test_edit_omission_preserves_manual_roster_and_explicit_empty_selection_clears_it(): void
    {
        $ana = $this->member(null, 'Ana');
        $rifa = $this->rifa(null, ['vendedor_ids' => [$ana->id], 'status' => 'finalizada']);
        $data = ['nome' => $rifa->nome, 'valor_numero' => 10, 'status' => 'finalizada'];
        $this->actingAs($this->admin())->put(route('rifas.update', $rifa), $data)->assertSessionHasNoErrors();
        $this->assertSame(1, $rifa->vendedoresSelecionados()->count());
        $this->put(route('rifas.update', $rifa), $data + ['atualizar_vendedores' => 1])->assertSessionHasNoErrors();
        $this->assertSame(0, $rifa->vendedoresSelecionados()->count());
    }

    public function test_manually_selected_seller_can_sell_but_unselected_member_cannot(): void
    {
        $ana = $this->member(null, 'Ana'); $other = $this->member(null, 'Outro');
        $rifa = $this->rifa(null, ['vendedor_ids' => [$ana->id]]);
        $this->actingAs($this->admin());
        $data = ['numero_ids' => [$rifa->numeros()->first()->id], 'comprador_nome' => 'Comprador'];
        $this->post(route('rifas.vendas.rapida.store', $rifa), $data + ['vendedor_id' => $other->id])->assertSessionHasErrors('vendedor_id');
        $this->post(route('rifas.vendas.rapida.store', $rifa), $data + ['vendedor_id' => $ana->id])->assertRedirect();
        $this->assertSame(1, DB::table('rifa_vendas')->count());
    }

    public function test_removing_individual_selection_preserves_old_cards_and_excludes_seller_from_future_distribution(): void
    {
        $ana = $this->member(null, 'Ana'); $bia = $this->member(null, 'Bia');
        $rifa = $this->rifa(null, ['vendedor_ids' => [$ana->id, $bia->id]]);
        app(RifaService::class)->atualizar($rifa, ['vendedor_ids' => [$ana->id]]);
        $this->assertSame([$ana->id], $rifa->membrosVendedores(false)->pluck('members.id')->all());
        $this->assertSame([$ana->id, $bia->id], $rifa->membrosVendedores()->pluck('members.id')->all());
        app(RifaService::class)->adicionarNumeros($rifa, 5);
        $this->assertSame($ana->id, $rifa->cartelas()->latest('id')->first()->vendedor_id);
        $this->assertSame(2, $rifa->cartelas()->where('vendedor_id', $bia->id)->count());
    }

    public function test_create_and_expand_routes_generate_and_distribute_new_cards(): void
    {
        $department = $this->department(); $ana = $this->member($department, 'Ana');
        $this->actingAs($this->admin())->post(route('rifas.store'), [
            'nome' => 'Nova rifa', 'quantidade_numeros' => 10, 'numeros_por_cartela' => 5,
            'valor_numero' => 10, 'status' => 'ativa', 'department_id' => $department->id,
        ])->assertSessionHasNoErrors()->assertRedirect(route('rifas.show', Rifa::firstOrFail()));
        $rifa = Rifa::firstOrFail();
        $this->post(route('rifas.numeros.adicionar', $rifa), ['quantidade' => 5])->assertSessionHasNoErrors()->assertRedirect(route('rifas.cartelas.index', $rifa));
        $this->assertSame(3, $rifa->cartelas()->where('vendedor_id', $ana->id)->count());
        $this->assertSame(15, $rifa->fresh()->quantidade_numeros);
        Http::assertNothingSent();
    }

    public function test_large_card_generation_uses_all_numbers_once(): void
    {
        $rifa = $this->rifa(null, ['quantidade_numeros' => 1200, 'numeros_por_cartela' => 1200]);
        $this->assertSame(1200, $rifa->cartelas()->first()->numeros()->count());
        $this->assertSame(1200, DB::table('cartela_numero')->distinct()->count('numero_id'));
    }

    public function test_sales_for_department_rifa_reject_sellers_from_other_departments(): void
    {
        $department = $this->department(); $this->member($department, 'Ana'); $outsider = $this->member(null, 'Outro');
        $rifa = $this->rifa($department);
        $this->actingAs($this->admin())->post(route('rifas.vendas.rapida.store', $rifa), [
            'numero_ids' => [$rifa->numeros()->first()->id], 'vendedor_id' => $outsider->id, 'comprador_nome' => 'Comprador',
        ])->assertSessionHasErrors('vendedor_id');
        $this->assertSame(0, DB::table('rifa_vendas')->count());
        $this->assertSame(23, $rifa->numeros()->where('status', 'disponivel')->count());
    }

    public function test_new_numbers_are_rolled_back_when_department_has_no_active_sellers(): void
    {
        $department = $this->department(); $ana = $this->member($department, 'Ana'); $rifa = $this->rifa($department);
        $ana->update(['status' => 'inativo']);
        $this->actingAs($this->admin())->post(route('rifas.numeros.adicionar', $rifa), ['quantidade' => 5])->assertSessionHasErrors('department_id');
        $this->assertSame(23, $rifa->fresh()->quantidade_numeros);
        $this->assertSame(5, $rifa->cartelas()->count());
    }

    public function test_legacy_distribution_preserves_sold_reserved_and_already_assigned_cards(): void
    {
        $department = $this->department(); $ana = $this->member($department, 'Ana');
        $rifa = $this->rifa(); $cartelas = $rifa->cartelas()->orderBy('id')->get();
        $cartelas[0]->numeros()->first()->update(['status' => 'vendido', 'vendedor_id' => $ana->id, 'comprador_nome' => 'Comprador']);
        $cartelas[1]->numeros()->first()->update(['status' => 'reservado']);
        $cartelas[2]->update(['vendedor_id' => $ana->id]);
        $rifa->update(['department_id' => $department->id]);
        $this->assertSame(2, app(RifaService::class)->distribuirCartelas($rifa));
        $this->assertNull($cartelas[0]->fresh()->vendedor_id); $this->assertNull($cartelas[1]->fresh()->vendedor_id);
        $this->assertSame($ana->id, $cartelas[2]->fresh()->vendedor_id);
    }

    public function test_added_numbers_continue_sequence_without_changing_partial_cards_or_sales(): void
    {
        $department = $this->department(); $ana = $this->member($department, 'Ana'); $this->member($department, 'Bia');
        $rifa = $this->rifa($department); $before = $rifa->cartelas()->with('numeros')->get();
        $numero = $rifa->numeros()->first();
        app(VendaRifaService::class)->registrarVendaRapida($rifa, ['numero_ids' => [$numero->id], 'vendedor_id' => $ana->id, 'comprador_nome' => 'Comprador']);
        $saleBefore = $numero->fresh()->getAttributes();
        app(RifaService::class)->adicionarNumeros($rifa, 7);
        $this->assertSame(30, $rifa->fresh()->quantidade_numeros);
        $this->assertSame(30, $rifa->numeros()->count()); $this->assertSame(7, $rifa->cartelas()->count());
        $this->assertSame('0030', $rifa->numeros()->orderByDesc('id')->first()->numero);
        foreach ($before as $cartela) {
            $this->assertSame($cartela->vendedor_id, $cartela->fresh()->vendedor_id);
            $this->assertSame($cartela->numeros->pluck('id')->all(), $cartela->numeros()->pluck('numeros_rifa.id')->all());
        }
        $this->assertSame($saleBefore, $numero->fresh()->getAttributes());
        $this->assertSame(30, DB::table('cartela_numero')->distinct()->count('numero_id'));
        $this->assertSame(0, $rifa->cartelas()->whereNull('vendedor_id')->count());
    }

    public function test_number_sequence_crosses_digit_boundary_without_renumbering_existing_numbers(): void
    {
        $rifa = $this->rifa(null, ['quantidade_numeros' => 2, 'numeros_por_cartela' => 2]);
        $rifa->numeros()->orderByDesc('id')->first()->update(['numero' => '9999']);
        app(RifaService::class)->adicionarNumeros($rifa, 2);
        $this->assertSame(['0001', '9999', '10000', '10001'], $rifa->numeros()->orderBy('id')->pluck('numero')->all());
    }

    public function test_closed_rifas_and_limit_overflow_reject_addition_without_changes(): void
    {
        $this->actingAs($this->admin()); $rifa = $this->rifa();
        $this->post(route('rifas.numeros.adicionar', $rifa), ['quantidade' => 100000])->assertSessionHasErrors('quantidade');
        $rifa->update(['status' => 'finalizada']);
        $this->post(route('rifas.numeros.adicionar', $rifa), ['quantidade' => 1])->assertSessionHasErrors('quantidade');
        $this->assertSame(23, $rifa->numeros()->count()); $this->assertSame(5, $rifa->cartelas()->count());
    }

    public function test_department_cannot_be_changed_after_distribution(): void
    {
        $department = $this->department(); $this->member($department, 'Ana'); $rifa = $this->rifa($department);
        $this->actingAs($this->admin())->put(route('rifas.update', $rifa), [
            'nome' => $rifa->nome, 'valor_numero' => 10, 'status' => 'ativa', 'department_id' => null,
        ])->assertSessionHasErrors('department_id');
        $this->assertSame($department->id, $rifa->fresh()->department_id);
    }

    public function test_views_offer_department_assignment_pdf_whatsapp_and_seller_filter(): void
    {
        $department = $this->department(); $ana = $this->member($department, 'Ana'); $bia = $this->member($department, 'Bia');
        $rifa = $this->rifa($department);
        $this->actingAs($this->admin())->get(route('rifas.create'))->assertOk()->assertSee('Departamento responsável');
        $this->get(route('rifas.show', $rifa))->assertOk()->assertSee('Adicionar números');
        $this->get(route('rifas.cartelas.index', [$rifa, 'vendedor_id' => $ana->id]))->assertOk()
            ->assertSee('Enviar por WhatsApp')->assertSee('Baixar cartela em PDF')
            ->assertViewHas('cartelas', fn ($cards) => $cards->count() === 3 && $cards->every(fn ($c) => $c->vendedor_id === $ana->id));
    }

    public function test_pdf_contains_numbers_and_seller_without_buyer_details(): void
    {
        $department = $this->department(); $this->member($department, 'Ana'); $rifa = $this->rifa($department);
        $cartela = $rifa->cartelas()->with('numeros', 'vendedor')->first();
        $cartela->numeros()->first()->update(['comprador_nome' => 'Nome privado', 'comprador_telefone' => 'Telefone privado']);
        $html = view('rifas.cartelas.pdf', compact('rifa', 'cartela'))->render();
        $this->assertStringContainsString('Ana', $html); $this->assertStringContainsString('0001', $html);
        $this->assertStringNotContainsString('Nome privado', $html); $this->assertStringNotContainsString('Telefone privado', $html);
        $response = $this->actingAs($this->admin())->get(route('rifas.cartelas.pdf', [$rifa, $cartela]));
        $response->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    public static function whatsappResults(): array
    {
        return ['success' => [true], 'failure' => [false]];
    }

    /** @dataProvider whatsappResults */
    public function test_whatsapp_sends_only_assigned_card_to_seller_and_tracks_confirmed_send(bool $success): void
    {
        $department = $this->department(); $this->member($department, 'Ana'); $rifa = $this->rifa($department);
        $cartela = $rifa->cartelas()->first(); $temporaryPath = null;
        $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class); $pdf->shouldReceive('setPaper')->with('a4')->andReturnSelf(); $pdf->shouldReceive('output')->once()->andReturn('%PDF-fake');
        Pdf::shouldReceive('loadView')->once()->with('rifas.cartelas.pdf', \Mockery::on(fn ($data) => $data['cartela']->id === $cartela->id))->andReturn($pdf);
        $whatsapp = \Mockery::mock(WhatsAppService::class);
        $whatsapp->shouldReceive('isConfigurado')->once()->andReturn(true);
        $whatsapp->shouldReceive('enviarDocumentoArquivo')->once()->with('(61) 99999-0000', \Mockery::on(function ($path) use (&$temporaryPath) {
            $temporaryPath = $path; return is_file($path) && file_get_contents($path) === '%PDF-fake';
        }), 'rifa-'.$rifa->id.'-cartela-'.$cartela->id.'.pdf', \Mockery::on(fn ($caption) => str_contains($caption, 'Ana') && str_contains($caption, '10,00')))->andReturn(['success' => $success]);
        $this->app->instance(WhatsAppService::class, $whatsapp);
        $this->actingAs($this->admin())->from(route('rifas.cartelas.index', $rifa))
            ->post(route('rifas.cartelas.whatsapp', [$rifa, $cartela]))->assertRedirect(route('rifas.cartelas.index', $rifa))->assertSessionHas($success ? 'success' : 'error');
        $this->assertSame($success, $cartela->fresh()->whatsapp_enviado_em !== null);
        $this->assertFileDoesNotExist($temporaryPath); Http::assertNothingSent();
    }

    public function test_whatsapp_cannot_send_card_from_another_rifa_or_without_seller_phone(): void
    {
        $rifa = $this->rifa(); $other = $this->rifa(); $cartela = $rifa->cartelas()->first();
        $whatsapp = \Mockery::mock(WhatsAppService::class); $whatsapp->shouldNotReceive('enviarDocumentoArquivo');
        $this->app->instance(WhatsAppService::class, $whatsapp);
        $this->actingAs($this->admin())->post(route('rifas.cartelas.whatsapp', [$other, $cartela]))->assertNotFound();
        $this->post(route('rifas.cartelas.whatsapp', [$rifa, $cartela]))->assertSessionHas('error');
        $this->get(route('rifas.cartelas.pdf', [$other, $cartela]))->assertNotFound();
    }

    public function test_view_only_user_cannot_distribute_add_numbers_or_send_whatsapp(): void
    {
        $user = User::create(['name' => 'Leitor', 'email' => 'viewer@example.test', 'password' => Hash::make('password')]);
        $user->setRelation('permissions', collect([new Permission(['key' => 'rifas.index.view'])]));
        $user->setRelation('member', null);
        $rifa = $this->rifa(); $cartela = $rifa->cartelas()->first();
        $this->actingAs($user)->getJson(route('rifas.cartelas.index', $rifa))->assertOk();
        $this->postJson(route('rifas.cartelas.distribuir', $rifa))->assertForbidden();
        $this->postJson(route('rifas.numeros.adicionar', $rifa), ['quantidade' => 5])->assertForbidden();
        $this->postJson(route('rifas.cartelas.whatsapp', [$rifa, $cartela]))->assertForbidden();
        $this->assertSame(23, $rifa->numeros()->count());
    }

    public function test_non_admin_manager_can_distribute_cards(): void
    {
        $user = User::create(['name' => 'Gestor', 'email' => 'manager@example.test', 'password' => Hash::make('password')]);
        $user->setRelation('permissions', collect([new Permission(['key' => 'rifas.index.manage'])]));
        $user->setRelation('member', null);
        $department = $this->department(); $this->member($department, 'Ana'); $rifa = $this->rifa();
        $rifa->update(['department_id' => $department->id]);
        $this->actingAs($user)->post(route('rifas.cartelas.distribuir', $rifa))->assertSessionHas('success');
        $this->assertSame(0, $rifa->cartelas()->whereNull('vendedor_id')->count());
    }
}
