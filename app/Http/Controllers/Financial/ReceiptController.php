<?php

namespace App\Http\Controllers\Financial;

use App\Http\Controllers\Controller;
use App\Models\FinancialCategory;
use App\Models\FinancialNotificationLog;
use App\Models\FinancialTransaction;
use App\Models\FinancialTransactionAttachment;
use App\Models\User;
use App\Support\FinancialReceiptPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

/**
 * Localização dos recibos do financeiro em três visões: arquivos guardados
 * (upload do usuário ou PDF emitido pelo sistema), dízimos e ofertas cujo
 * recibo é emitido sob demanda e despesas pagas sem nenhum comprovante.
 */
class ReceiptController extends Controller
{
    private const TABLE = 'financial_transaction_attachments';

    public function index(Request $request)
    {
        $this->authorize('viewAny', FinancialTransaction::class);

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $allowedTypes = $this->allowedTypes();
        $pendingAvailable = in_array('despesa', $allowedTypes, true);
        $revenuesAvailable = in_array('receita', $allowedTypes, true);

        $tab = $this->resolveTab($request, $pendingAvailable, $revenuesAvailable);
        $perPage = (int) $request->input('per_page', 50);
        $perPage = in_array($perPage, [50, 100, 200], true) ? $perPage : 50;

        $receipts = null;
        $revenues = null;
        $pending = null;

        if ($tab === 'arquivos') {
            $receipts = $this->receiptsQuery($request, $startDate, $endDate, $allowedTypes)
                ->paginate($perPage)
                ->withQueryString();
        } elseif ($tab === 'receitas') {
            $revenues = $this->revenuesQuery($request, $startDate, $endDate)
                ->paginate($perPage)
                ->withQueryString();
        } else {
            $pending = $this->pendingQuery($request, $startDate, $endDate)
                ->paginate($perPage)
                ->withQueryString();
        }

        return view('financial.receipts.index', [
            'tab' => $tab,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'receipts' => $receipts,
            'revenues' => $revenues,
            'pending' => $pending,
            'summary' => $this->summary($request, $startDate, $endDate, $allowedTypes, $pendingAvailable),
            'categories' => $this->filterCategories($allowedTypes),
            'allowedTypes' => $allowedTypes,
            'pendingAvailable' => $pendingAvailable,
            'revenuesAvailable' => $revenuesAvailable,
        ]);
    }

    private function resolveTab(Request $request, bool $pendingAvailable, bool $revenuesAvailable): string
    {
        $tab = (string) $request->input('tab', 'arquivos');

        if ($tab === 'pendentes' && $pendingAvailable) {
            return 'pendentes';
        }

        if ($tab === 'receitas' && $revenuesAvailable) {
            return 'receitas';
        }

        return 'arquivos';
    }

    public function export(Request $request)
    {
        $this->authorize('export', FinancialTransaction::class);

        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));
        $allowedTypes = $this->allowedTypes();
        $tab = $this->resolveTab($request, in_array('despesa', $allowedTypes, true), in_array('receita', $allowedTypes, true));

        $rows = match ($tab) {
            'pendentes' => $this->pendingQuery($request, $startDate, $endDate)->get(),
            'receitas' => $this->revenuesQuery($request, $startDate, $endDate)->get(),
            default => $this->receiptsQuery($request, $startDate, $endDate, $allowedTypes)->get(),
        };

        $filename = match ($tab) {
            'pendentes' => 'despesas_sem_recibo_',
            'receitas' => 'recibos_dizimos_ofertas_',
            default => 'recibos_',
        }.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($rows, $tab) {
            $file = fopen('php://output', 'w');

            // BOM para o Excel reconhecer o UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

            fputcsv($file, match ($tab) {
                'pendentes' => ['Recibo nº', 'Data', 'Descrição', 'Pago à', 'Categoria', 'Valor', 'Doc nº'],
                'receitas' => ['Recibo nº', 'Data', 'Descrição', 'Recebido de', 'Categoria', 'Valor', 'Comprovante enviado em', 'Arquivos'],
                default => ['Recibo nº', 'Data', 'Tipo', 'Descrição', 'Recebido de / Pago à', 'Categoria', 'Valor', 'Origem', 'Arquivo'],
            }, ';');

            foreach ($rows as $row) {
                $transaction = $tab === 'arquivos' ? $row->transaction : $row;

                if ($transaction === null) {
                    continue;
                }

                $line = [
                    FinancialReceiptPresenter::number($transaction),
                    $transaction->transaction_date->format('d/m/Y'),
                ];

                if ($tab === 'arquivos') {
                    $line[] = ucfirst($transaction->type);
                }

                $line = array_merge($line, [
                    $transaction->description,
                    $transaction->listingPersonName() ?: '-',
                    $transaction->category?->name ?: '-',
                    number_format((float) $transaction->amount, 2, ',', '.'),
                ]);

                $line = array_merge($line, match ($tab) {
                    'pendentes' => [$transaction->document_number ?: '-'],
                    'receitas' => [
                        $transaction->notificationLogs->first()?->created_at?->format('d/m/Y H:i') ?: 'Não enviado',
                        (string) $transaction->attachments_count,
                    ],
                    default => [
                        $row->isSystemGenerated() ? 'Emitido pelo sistema' : 'Enviado por upload',
                        $row->file_name,
                    ],
                });

                fputcsv($file, $line, ';');
            }

            fclose($file);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
        ]);
    }

    /**
     * Abre o arquivo do recibo no navegador.
     */
    public function file(FinancialTransactionAttachment $attachment)
    {
        [$disk, $path] = $this->authorizeFile($attachment);

        return $disk->response($path, $attachment->file_name);
    }

    public function download(FinancialTransactionAttachment $attachment)
    {
        [$disk, $path] = $this->authorizeFile($attachment);

        return $disk->download($path, $attachment->file_name);
    }

    /**
     * @return array{0: \Illuminate\Contracts\Filesystem\Filesystem, 1: string}
     */
    private function authorizeFile(FinancialTransactionAttachment $attachment): array
    {
        $transaction = $attachment->transaction;
        abort_if($transaction === null, 404);
        $this->authorize('receipt', $transaction);

        $disk = Storage::disk('public');
        abort_unless($disk->exists($attachment->file_path), 404, 'Arquivo do recibo não encontrado.');

        return [$disk, $attachment->file_path];
    }

    /**
     * Recibos arquivados (upload do usuário ou emitidos pelo sistema).
     *
     * @param  array<int, string>  $allowedTypes
     */
    private function receiptsQuery(Request $request, string $startDate, string $endDate, array $allowedTypes): Builder
    {
        $query = FinancialTransactionAttachment::query()
            ->select(self::TABLE.'.*')
            ->join('financial_transactions as t', 't.id', '=', self::TABLE.'.transaction_id')
            ->whereNull('t.deleted_at')
            ->with(['transaction.member', 'transaction.contact', 'transaction.category'])
            ->whereBetween('t.transaction_date', [$startDate, $endDate])
            ->whereIn('t.type', $this->selectedTypes($request, $allowedTypes))
            ->orderByDesc('t.transaction_date')
            ->orderByDesc(self::TABLE.'.id');

        if ($request->filled('category_id')) {
            $query->where('t.category_id', $request->input('category_id'));
        }

        $origin = $request->input('origin');
        if ($origin === 'sistema') {
            $query->where(self::TABLE.'.file_path', 'like', FinancialTransactionAttachment::GENERATED_DIR.'/%');
        } elseif ($origin === 'upload') {
            $query->where(self::TABLE.'.file_path', 'not like', FinancialTransactionAttachment::GENERATED_DIR.'/%');
        }

        if ($request->filled('search')) {
            $this->applySearch($query, (string) $request->input('search'), 't', 'transaction.');
        }

        return $query;
    }

    /**
     * Dízimos e ofertas recebidos: o recibo é emitido sob demanda (impressão ou
     * WhatsApp), por isso a lista mostra o que já foi enviado ao dizimista.
     */
    private function revenuesQuery(Request $request, string $startDate, string $endDate): Builder
    {
        $query = FinancialTransaction::query()
            ->with(['member', 'category', 'notificationLogs' => fn ($q) => $q
                ->where('notification_type', FinancialNotificationLog::TYPE_RECEIPT_MEMBER)
                ->where('status', 'sent')
                ->latest('id')])
            ->withCount('attachments')
            ->receitas()
            ->where('is_paid', true)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('search')) {
            $this->applySearch($query, (string) $request->input('search'), 'financial_transactions', '');
        }

        return $query;
    }

    /**
     * Despesas já pagas que seguem sem nenhum recibo arquivado.
     */
    private function pendingQuery(Request $request, string $startDate, string $endDate): Builder
    {
        $query = FinancialTransaction::query()
            ->with(['member', 'contact', 'category'])
            ->despesas()
            ->where('is_paid', true)
            ->whereDoesntHave('attachments')
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->orderByDesc('transaction_date')
            ->orderByDesc('id');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('search')) {
            $this->applySearch($query, (string) $request->input('search'), 'financial_transactions', '');
        }

        return $query;
    }

    /**
     * Busca por descrição, pessoa, documento, nº do recibo e nome do arquivo.
     *
     * @param  string  $table  tabela (ou alias) das transações na consulta
     * @param  string  $relation  prefixo das relações a partir do modelo consultado
     */
    private function applySearch(Builder $query, string $term, string $table, string $relation): void
    {
        $like = '%'.$term.'%';
        $receiptNumber = ltrim(preg_replace('/\D/', '', $term) ?? '', '0');

        $query->where(function (Builder $q) use ($like, $receiptNumber, $table, $relation) {
            $q->where($table.'.description', 'like', $like)
                ->orWhere($table.'.received_from_other', 'like', $like)
                ->orWhere($table.'.document_number', 'like', $like)
                ->orWhereHas($relation.'member', fn (Builder $m) => $m->where('name', 'like', $like))
                ->orWhereHas($relation.'contact', fn (Builder $c) => $c->where('name', 'like', $like));

            if ($relation !== '') {
                $q->orWhere(self::TABLE.'.file_name', 'like', $like);
            }

            if ($receiptNumber !== '') {
                $q->orWhere($table.'.id', (int) $receiptNumber);
            }
        });
    }

    /**
     * @param  array<int, string>  $allowedTypes
     * @return array{total: int, uploaded: int, generated: int, revenues: int, pending: int, pending_amount: float}
     */
    private function summary(
        Request $request,
        string $startDate,
        string $endDate,
        array $allowedTypes,
        bool $pendingAvailable
    ): array {
        $total = $this->receiptsQuery($request, $startDate, $endDate, $allowedTypes)->count();
        $generated = $this->receiptsQuery($request, $startDate, $endDate, $allowedTypes)
            ->where(self::TABLE.'.file_path', 'like', FinancialTransactionAttachment::GENERATED_DIR.'/%')
            ->count();

        return [
            'total' => $total,
            'uploaded' => $total - $generated,
            'generated' => $generated,
            'revenues' => in_array('receita', $allowedTypes, true)
                ? $this->revenuesQuery($request, $startDate, $endDate)->count()
                : 0,
            'pending' => $pendingAvailable ? $this->pendingQuery($request, $startDate, $endDate)->count() : 0,
            'pending_amount' => $pendingAvailable
                ? (float) $this->pendingQuery($request, $startDate, $endDate)->sum('amount')
                : 0.0,
        ];
    }

    /**
     * Tipos que o usuário pode ver, restringidos pelo filtro da tela.
     *
     * @param  array<int, string>  $allowedTypes
     * @return array<int, string>
     */
    private function selectedTypes(Request $request, array $allowedTypes): array
    {
        $selected = $request->input('type', []);
        $selected = is_array($selected) ? $selected : [$selected];
        $selected = array_values(array_intersect($allowedTypes, $selected));

        return $selected !== [] ? $selected : $allowedTypes;
    }

    /**
     * @return array<int, string>
     */
    private function allowedTypes(): array
    {
        /** @var User $user */
        $user = Auth::user();

        if ($user->is_admin) {
            return ['receita', 'despesa'];
        }

        $types = [];

        foreach (['receita' => 'receitas', 'despesa' => 'despesas'] as $type => $group) {
            if ($user->hasPermission("financial.{$group}.view") || $user->hasPermission("financial.{$group}.manage")) {
                $types[] = $type;
            }
        }

        return $types;
    }

    /**
     * @param  array<int, string>  $allowedTypes
     */
    private function filterCategories(array $allowedTypes)
    {
        return FinancialCategory::whereIn('type', $allowedTypes)
            ->orderBy('type')
            ->orderBy('name')
            ->get();
    }
}
