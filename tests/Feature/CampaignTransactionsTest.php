<?php

namespace Tests\Feature;

use App\Http\Controllers\Eventos\CampaignController;
use App\Http\Controllers\Eventos\CampaignExpenseController;
use App\Models\Campaign;
use App\Models\CampaignExpense;
use App\Models\User;
use App\Policies\Eventos\CampaignPolicy;
use App\Services\Authorization\EventosRouteAuthorizer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CampaignTransactionsTest extends TestCase
{
    private Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.database', ':memory:');
        Storage::fake('public');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->boolean('is_admin')->default(true);
            $table->timestamps();
        });
        Schema::create('departments', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('members', function (Blueprint $table) {
            $table->id();
        });

        foreach (['2026_08_07_100000_create_campaigns_tables.php', '2026_10_16_100100_create_campaign_expenses_tables.php', '2026_10_18_100000_add_kind_to_campaign_expenses_table.php'] as $file) {
            (require database_path('migrations/' . $file))->up();
        }

        $user = User::create(['name' => 'Gestor', 'email' => 'eventos@test.local', 'password' => bcrypt('secret'), 'is_admin' => true]);
        $this->actingAs($user);
        $this->campaign = Campaign::create(['name' => 'Evento solidário', 'installment_amount' => 100, 'goal_amount' => 1000, 'created_by' => $user->id]);
    }

    private function entry(array $attributes = []): CampaignExpense
    {
        return $this->campaign->expenses()->create(array_merge([
            'kind' => 'receita',
            'description' => 'Doação da comunidade',
            'category' => 'doacao_externa',
            'amount' => 250,
            'expense_date' => '2026-10-18',
            'supplier' => 'Doador externo',
            'payment_method' => 'pix',
            'created_by' => auth()->id(),
        ], $attributes));
    }

    private function sponsorPayment(): void
    {
        $sponsor = $this->campaign->sponsors()->create(['name' => 'Patrocinador', 'created_by' => auth()->id()]);
        $sponsor->installments()->create(['installment_number' => 1, 'amount' => 100, 'status' => 'pago', 'paid_at' => '2026-10-18 12:00:00', 'payment_method' => 'pix']);
    }

    public function test_income_can_be_created_without_receipt_and_included_in_balance(): void
    {
        $this->withoutExceptionHandling();
        $this->post(route('eventos.campaigns.expenses.store', $this->campaign), [
            'kind' => 'receita', 'description' => 'Doação externa', 'category' => 'doacao_externa',
            'amount' => 250, 'expense_date' => '2026-10-18', 'supplier' => 'Maria', 'payment_method' => 'pix',
        ])->assertSessionHasNoErrors()->assertRedirect(route('eventos.campaigns.expenses.index', $this->campaign));

        $this->assertDatabaseHas('campaign_expenses', ['kind' => 'receita', 'supplier' => 'Maria', 'amount' => 250]);
        $this->assertSame(250.0, $this->campaign->totalExtraIncome());
        $this->assertSame(0.0, $this->campaign->totalExpenses());
        $this->assertSame(250.0, $this->campaign->balance());
        $this->assertSame(25.0, $this->campaign->progressPercentage());
    }

    public function test_expense_still_requires_receipt_and_rejects_income_category(): void
    {
        $data = ['kind' => 'gasto', 'description' => 'Aluguel', 'category' => 'aluguel', 'amount' => 50, 'expense_date' => '2026-10-18'];
        $this->post(route('eventos.campaigns.expenses.store', $this->campaign), $data)->assertSessionHasErrors('attachments');
        session()->forget('errors');
        $data['attachments'] = [UploadedFile::fake()->image('nota.jpg')];
        $this->post(route('eventos.campaigns.expenses.store', $this->campaign), $data)->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(50.0, $this->campaign->totalExpenses());
        $this->assertDatabaseCount('campaign_expense_attachments', 1);
        $data['kind'] = 'receita';
        $this->post(route('eventos.campaigns.expenses.store', $this->campaign), $data)->assertSessionHasErrors('category');
    }

    public function test_updating_and_deleting_income_recalculates_balance(): void
    {
        $this->withoutExceptionHandling();
        $income = $this->entry();
        $this->sponsorPayment();
        $this->entry(['kind' => 'gasto', 'category' => 'aluguel', 'amount' => 50]);

        $this->put(route('eventos.campaigns.expenses.update', $income), [
            'kind' => 'receita', 'description' => 'Venda na cantina', 'category' => 'venda', 'amount' => 300, 'expense_date' => '2026-10-18',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $this->assertSame(350.0, $this->campaign->balance());

        $this->delete(route('eventos.campaigns.expenses.destroy', $income))->assertSessionHasNoErrors();
        $this->assertSame(50.0, $this->campaign->balance());
    }

    public function test_income_cannot_be_changed_to_expense_without_receipt(): void
    {
        $income = $this->entry();
        $this->put(route('eventos.campaigns.expenses.update', $income), [
            'kind' => 'gasto', 'description' => 'Aluguel', 'category' => 'aluguel', 'amount' => 250, 'expense_date' => '2026-10-18',
        ])->assertSessionHasErrors('attachments');
        $this->assertSame('receita', $income->fresh()->kind);
    }

    public function test_lists_filter_income_and_keep_separate_totals(): void
    {
        $income = $this->entry();
        $this->entry(['kind' => 'gasto', 'category' => 'aluguel', 'amount' => 50]);
        $controller = app(CampaignExpenseController::class);
        $request = Request::create('/', 'GET', ['kind' => 'receita', 'q' => 'Doador externo']);
        $data = $controller->index($request, $this->campaign)->getData();
        $this->assertSame([$income->id], $data['expenses']->pluck('id')->all());
        $this->assertSame(250.0, $data['totals']['income']);
        $this->assertSame(50.0, $data['totals']['spent']);
        $data = $controller->all($request)->getData();
        $this->assertSame([$income->id], $data['expenses']->pluck('id')->all());
        $this->assertSame(250.0, $data['grandTotalIncome']);
        $this->assertSame(50.0, $data['grandTotalExpenses']);
    }

    public function test_reports_include_income_details_and_filter_transaction_dates(): void
    {
        $income = $this->entry();
        $this->entry(['expense_date' => '2026-09-01', 'amount' => 70]);
        $this->entry(['kind' => 'gasto', 'category' => 'aluguel', 'amount' => 50]);
        $this->sponsorPayment();
        $controller = app(CampaignController::class);
        $request = Request::create('/', 'GET', ['from' => '2026-10-01', 'to' => '2026-10-31']);
        $data = $controller->report($request, $this->campaign)->getData();
        $this->assertSame([$income->id], $data['incomes']->pluck('id')->all());
        $this->assertSame(1, $data['expenses']->count());
        $this->assertSame(320.0, $data['metrics']['income']);
        $this->assertSame(370.0, $data['metrics']['balance']);
        $this->assertSame(42.0, $data['metrics']['progress']);

        $csv = $controller->reportExcel($request, $this->campaign);
        ob_start();
        $csv->sendContent();
        $content = ob_get_clean();
        $this->assertStringContainsString('RECEITAS EXTRAS', $content);
        $this->assertStringContainsString('Doador externo', $content);
        $this->assertStringContainsString('370,00', $content);
        $this->assertStringNotContainsString('01/09/2026', $content);

        $html = view('eventos.campaigns.pdf.report', $data)->render();
        $this->assertStringContainsString('Receitas extras', $html);
        $this->assertStringContainsString('Doador externo', $html);
        $this->assertStringContainsString('R$ 420,00', $html);
        $this->assertStringContainsString('R$ 370,00', $html);
        $this->assertStringStartsWith('%PDF-', $controller->reportPdf($request, $this->campaign)->getContent());
    }

    public function test_migration_preserves_existing_expenses_as_expenses(): void
    {
        $migration = require database_path('migrations/2026_10_18_100000_add_kind_to_campaign_expenses_table.php');
        $migration->down();
        $id = DB::table('campaign_expenses')->insertGetId([
            'campaign_id' => $this->campaign->id, 'description' => 'Gasto anterior', 'category' => 'aluguel',
            'amount' => 75, 'expense_date' => '2026-10-01', 'created_by' => auth()->id(),
        ]);
        $migration->up();
        $this->assertSame('gasto', CampaignExpense::findOrFail($id)->kind);
        $this->assertSame(75.0, $this->campaign->totalExpenses());
        $this->assertSame(0.0, $this->campaign->totalExtraIncome());
    }

    public function test_legacy_transaction_routes_keep_edit_and_delete_permissions(): void
    {
        $user = new User(['is_admin' => false]);
        foreach (['update' => 'edit', 'destroy' => 'delete'] as $route => $action) {
            $request = Request::create('/');
            $request->setRouteResolver(fn () => app('router')->getRoutes()->getByName('eventos.campaigns.expenses.legacy.' . $route));
            $policy = \Mockery::mock(CampaignPolicy::class);
            $policy->shouldReceive('action')->once()->with($user, $action)->andReturn(false);
            $response = (new EventosRouteAuthorizer($policy))->authorize($request, $user, fn ($message) => response($message, 403));
            $this->assertSame(403, $response->getStatusCode());
        }
    }
}
