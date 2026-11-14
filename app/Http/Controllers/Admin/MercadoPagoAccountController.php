<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\MercadoPagoAccount;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MercadoPagoAccountController extends Controller
{
    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()?->is_admin, 403);
        abort_unless(app()->environment(['local', 'testing']) || $request->isSecure(), 403, 'Use HTTPS para gerenciar credenciais de pagamento.');
    }

    public function index(Request $request)
    {
        $this->authorizeAdmin($request);
        return response()->view('admin.mercadopago.index', [
            'accounts' => MercadoPagoAccount::where('is_active', true)->orderBy('name')->get(),
            'editing' => null,
        ])->header('Cache-Control', 'no-store, private');
    }

    public function edit(Request $request, MercadoPagoAccount $account)
    {
        $this->authorizeAdmin($request);
        abort_unless($account->is_active, 404);
        return response()->view('admin.mercadopago.index', [
            'accounts' => MercadoPagoAccount::where('is_active', true)->orderBy('name')->get(),
            'editing' => $account,
        ])->header('Cache-Control', 'no-store, private');
    }

    public function store(Request $request)
    {
        return $this->save($request);
    }

    public function update(Request $request, MercadoPagoAccount $account)
    {
        return $this->save($request, $account);
    }

    private function save(Request $request, ?MercadoPagoAccount $previous = null)
    {
        $this->authorizeAdmin($request);
        if ($previous) abort_unless($previous->is_active, 409);
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'public_key' => ['required', 'string', 'max:255', 'regex:/^\S+$/'],
            'access_token' => [$previous ? 'nullable' : 'required', 'string', 'max:2000', 'regex:/^\S+$/'],
            'webhook_secret' => [$previous ? 'nullable' : 'required', 'string', 'max:2000', 'regex:/^\S+$/'],
            'statement_descriptor' => ['required', 'string', 'max:13', 'regex:/^[A-Za-z0-9 ]+$/'],
            'currency' => 'required|in:BRL',
            'notification_url' => ['required', 'url', 'max:1000', function ($attribute, $value, $fail) {
                $target = parse_url($value);
                $expected = parse_url(rtrim((string) config('app.url'), '/').'/webhooks/mercado-pago');
                if (($target['host'] ?? '') !== ($expected['host'] ?? '') || ($target['port'] ?? null) !== ($expected['port'] ?? null)
                    || ($target['path'] ?? '') !== ($expected['path'] ?? '') || isset($target['query']) || isset($target['fragment']) || isset($target['user'])
                    || (!app()->environment(['local', 'testing']) && ($target['scheme'] ?? '') !== 'https')) {
                    $fail('Use a URL HTTPS de webhook deste sistema, sem parâmetros adicionais.');
                }
            }],
            'sandbox' => 'required|boolean',
            'current_password' => 'required|current_password',
        ]);
        if ($previous && $request->filled('access_token') && $data['access_token'] !== $previous->access_token && !$request->filled('webhook_secret')) {
            throw ValidationException::withMessages(['webhook_secret' => 'Ao trocar o token, informe também o segredo de webhook da nova aplicação.']);
        }
        $data['access_token'] = $data['access_token'] ?? $previous?->access_token;
        $data['webhook_secret'] = $data['webhook_secret'] ?? $previous?->webhook_secret;
        unset($data['current_password']);
        try {
            $response = Http::withToken($data['access_token'])->acceptJson()->connectTimeout(5)->timeout(12)->get('https://api.mercadopago.com/users/me');
            $merchant = $response->successful() ? $response->json() : null;
        } catch (\Throwable $exception) {
            $merchant = null;
        }
        if (!is_array($merchant) || empty($merchant['id'])) {
            throw ValidationException::withMessages(['access_token' => 'Não foi possível validar esta conta no Mercado Pago. Confira o token ou tente novamente. Nenhuma configuração foi alterada.']);
        }
        if ($previous && (string) $merchant['id'] !== (string) $previous->merchant_id && $data['public_key'] === $previous->public_key) {
            throw ValidationException::withMessages(['public_key' => 'Informe a Public Key da nova conta.']);
        }
        $account = DB::transaction(function () use ($data, $merchant, $previous, $request) {
            if ($previous) {
                $locked = MercadoPagoAccount::whereKey($previous->id)->lockForUpdate()->firstOrFail();
                abort_unless($locked->is_active, 409);
                $locked->update(['is_active' => false]);
            }
            $account = MercadoPagoAccount::create($data + [
                'uuid' => (string) Str::uuid(), 'merchant_id' => (string) $merchant['id'],
                'merchant_name' => (string) ($merchant['nickname'] ?? $merchant['business_name'] ?? $merchant['id']),
                'previous_id' => $previous?->id, 'created_by' => $request->user()->id, 'is_active' => true,
            ]);
            // Existing payment rows retain the immutable previous credentials.
            if ($previous) Event::where('mercado_pago_account_id', $previous->id)->update(['mercado_pago_account_id' => $account->id]);
            AuditLogger::log('pagamentos', $previous ? 'account_rotated' : 'account_created', 'Configuração Mercado Pago alterada.', [
                'account_id' => $account->id, 'previous_id' => $previous?->id,
            ]);
            return $account;
        });
        return redirect()->route('admin.mercadopago.index')->with('success', 'Conta validada e salva. Selecione-a no cadastro do evento. Pagamentos anteriores mantêm a conta de origem.');
    }
}
